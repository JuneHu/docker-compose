<?php

/**
 * @file
 * Validates ftp (File Transfer Protocol) URIs as defined by generic RFC 1738.
 */

/**
 *
 */
class HTMLPurifier_URIScheme_ftp extends HTMLPurifier_URIScheme {
  /**
   * @type int
   */
  public $default_port = 21;

  /**
   * @type bool
   */
  // Usually.
  public $browsable = TRUE;

  /**
   * @type bool
   */
  public $hierarchical = TRUE;

  /**
   * @param HTMLPurifier_URI $uri
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool
   */
  public function doValidate(&$uri, $config, $context) {

    $uri->query = NULL;

    // Typecode check
    // reverse.
    $semicolon_pos = strrpos($uri->path, ';');
    if ($semicolon_pos !== FALSE) {
      // No semicolon.
      $type = substr($uri->path, $semicolon_pos + 1);
      $uri->path = substr($uri->path, 0, $semicolon_pos);
      $type_ret = '';
      if (strpos($type, '=') !== FALSE) {
        // Figure out whether or not the declaration is correct.
        list($key, $typecode) = explode('=', $type, 2);
        if ($key !== 'type') {
          // Invalid key, tack it back on encoded.
          $uri->path .= '%3B' . $type;
        }
        elseif ($typecode === 'a' || $typecode === 'i' || $typecode === 'd') {
          $type_ret = ";type=$typecode";
        }
      }
      else {
        $uri->path .= '%3B' . $type;
      }
      $uri->path = str_replace(';', '%3B', $uri->path);
      $uri->path .= $type_ret;
    }
    return TRUE;
  }

}

// vim: et sw=4 sts=4.
