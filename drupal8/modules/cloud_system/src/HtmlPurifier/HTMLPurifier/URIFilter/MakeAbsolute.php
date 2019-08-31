<?php

/**
 * @file
 * Does not support network paths.
 */

/**
 *
 */
class HTMLPurifier_URIFilter_MakeAbsolute extends HTMLPurifier_URIFilter {
  /**
   * @type string
   */
  public $name = 'MakeAbsolute';

  /**
   * @type
   */
  protected $base;

  /**
   * @type array
   */
  protected $basePathStack = [];

  /**
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function prepare($config) {

    $def = $config->getDefinition('URI');
    $this->base = $def->base;
    if (is_null($this->base)) {
      trigger_error(
            'URI.MakeAbsolute is being ignored due to lack of ' .
            'value for URI.Base configuration',
            E_USER_WARNING
        );
      return FALSE;
    }
    // Fragment is invalid for base URI.
    $this->base->fragment = NULL;
    $stack = explode('/', $this->base->path);
    // Discard last segment.
    array_pop($stack);
    // Do pre-parsing.
    $stack = $this->_collapseStack($stack);
    $this->basePathStack = $stack;
    return TRUE;
  }

  /**
   * @param HTMLPurifier_URI $uri
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool
   */
  public function filter(&$uri, $config, $context) {

    if (is_null($this->base)) {
      return TRUE;
    } // abort early
    if ($uri->path === '' && is_null($uri->scheme) &&
          is_null($uri->host) && is_null($uri->query) && is_null($uri->fragment)) {
      // Reference to current document.
      $uri = clone $this->base;
      return TRUE;
    }
    if (!is_null($uri->scheme)) {
      // Absolute URI already: don't change.
      if (!is_null($uri->host)) {
        return TRUE;
      }
      $scheme_obj = $uri->getSchemeObj($config, $context);
      if (!$scheme_obj) {
        // Scheme not recognized.
        return FALSE;
      }
      if (!$scheme_obj->hierarchical) {
        // non-hierarchal URI with explicit scheme, don't change.
        return TRUE;
      }
      // Special case: had a scheme but always is hierarchical and had no authority.
    }
    if (!is_null($uri->host)) {
      // Network path, don't bother.
      return TRUE;
    }
    if ($uri->path === '') {
      $uri->path = $this->base->path;
    }
    elseif ($uri->path[0] !== '/') {
      // Relative path, needs more complicated processing.
      $stack = explode('/', $uri->path);
      $new_stack = array_merge($this->basePathStack, $stack);
      if ($new_stack[0] !== '' && !is_null($this->base->host)) {
        array_unshift($new_stack, '');
      }
      $new_stack = $this->_collapseStack($new_stack);
      $uri->path = implode('/', $new_stack);
    }
    else {
      // Absolute path, but still we should collapse.
      $uri->path = implode('/', $this->_collapseStack(explode('/', $uri->path)));
    }
    // re-combine.
    $uri->scheme = $this->base->scheme;
    if (is_null($uri->userinfo)) {
      $uri->userinfo = $this->base->userinfo;
    }
    if (is_null($uri->host)) {
      $uri->host = $this->base->host;
    }
    if (is_null($uri->port)) {
      $uri->port = $this->base->port;
    }
    return TRUE;
  }

  /**
   * Resolve dots and double-dots in a path stack.
   *
   * @param array $stack
   *
   * @return array
   */
  private function _collapseStack($stack) {

    $result = [];
    $is_folder = FALSE;
    for ($i = 0; isset($stack[$i]); $i++) {
      $is_folder = FALSE;
      // Absorb an internally duplicated slash.
      if ($stack[$i] == '' && $i && isset($stack[$i + 1])) {
        continue;
      }
      if ($stack[$i] == '..') {
        if (!empty($result)) {
          $segment = array_pop($result);
          if ($segment === '' && empty($result)) {
            // Error case: attempted to back out too far:
            // restore the leading slash.
            $result[] = '';
          }
          elseif ($segment === '..') {
            // Cannot remove .. with ..
            $result[] = '..';
          }
        }
        else {
          // Relative path, preserve the double-dots.
          $result[] = '..';
        }
        $is_folder = TRUE;
        continue;
      }
      if ($stack[$i] == '.') {
        // Silently absorb.
        $is_folder = TRUE;
        continue;
      }
      $result[] = $stack[$i];
    }
    if ($is_folder) {
      $result[] = '';
    }
    return $result;
  }

}

// vim: et sw=4 sts=4.
