<?php

/**
 * @file
 * Implements data: URI for base64 encoded images supported by GD.
 */

/**
 *
 */
class HTMLPurifier_URIScheme_data extends HTMLPurifier_URIScheme {
  /**
   * @type bool
   */
  public $browsable = TRUE;

  /**
   * @type array
   */
  public $allowed_types = array(
        // You better write validation code for other types if you
        // decide to allow them.
    'image/jpeg' => TRUE,
    'image/gif' => TRUE,
    'image/png' => TRUE,
  );
  // This is actually irrelevant since we only write out the path
  // component.
  /**
   * @type bool
   */
  public $may_omit_host = TRUE;

  /**
   * @param HTMLPurifier_URI $uri
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool
   */
  public function doValidate(&$uri, $config, $context) {

    $result = explode(',', $uri->path, 2);
    $is_base64 = FALSE;
    $charset = NULL;
    $content_type = NULL;
    if (count($result) == 2) {
      list($metadata, $data) = $result;
      // Do some legwork on the metadata.
      $metas = explode(';', $metadata);
      while (!empty($metas)) {
        $cur = array_shift($metas);
        if ($cur == 'base64') {
          $is_base64 = TRUE;
          break;
        }
        if (substr($cur, 0, 8) == 'charset=') {
          // doesn't match if there are arbitrary spaces, but
          // whatever dude.
          if ($charset !== NULL) {
            continue;
          } // garbage
          $charset = substr($cur, 8); // not used.
        }
        else {
          if ($content_type !== NULL) {
            continue;
          } // garbage
          $content_type = $cur;
        }
      }
    }
    else {
      $data = $result[0];
    }
    if ($content_type !== NULL && empty($this->allowed_types[$content_type])) {
      return FALSE;
    }
    if ($charset !== NULL) {
      // error; we don't allow plaintext stuff.
      $charset = NULL;
    }
    $data = rawurldecode($data);
    if ($is_base64) {
      $raw_data = base64_decode($data);
    }
    else {
      $raw_data = $data;
    }
    if (strlen($raw_data) < 12) {
      // error; exif_imagetype throws exception with small files,
      // and this likely indicates a corrupt URI/failed parse anyway.
      return FALSE;
    }
    // XXX probably want to refactor this into a general mechanism
    // for filtering arbitrary content types.
    if (function_exists('sys_get_temp_dir')) {
      $file = tempnam(sys_get_temp_dir(), "");
    }
    else {
      $file = tempnam("/tmp", "");
    }
    file_put_contents($file, $raw_data);
    if (function_exists('exif_imagetype')) {
      $image_code = exif_imagetype($file);
      unlink($file);
    }
    elseif (function_exists('getimagesize')) {
      set_error_handler(array($this, 'muteErrorHandler'));
      $info = getimagesize($file);
      restore_error_handler();
      unlink($file);
      if ($info == FALSE) {
        return FALSE;
      }
      $image_code = $info[2];
    }
    else {
      trigger_error("could not find exif_imagetype or getimagesize functions", E_USER_ERROR);
    }
    $real_content_type = image_type_to_mime_type($image_code);
    if ($real_content_type != $content_type) {
      // we're nice guys; if the content type is something else we
      // support, change it over.
      if (empty($this->allowed_types[$real_content_type])) {
        return FALSE;
      }
      $content_type = $real_content_type;
    }
    // ok, it's kosher, rewrite what we need.
    $uri->userinfo = NULL;
    $uri->host = NULL;
    $uri->port = NULL;
    $uri->fragment = NULL;
    $uri->query = NULL;
    $uri->path = "$content_type;base64," . base64_encode($raw_data);
    return TRUE;
  }

  /**
   * @param int $errno
   * @param string $errstr
   */
  public function muteErrorHandler($errno, $errstr) {

  }

}
