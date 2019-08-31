<?php

/**
 * @file
 * Validates nntp (Network News Transfer Protocol) as defined by generic RFC 1738.
 */

/**
 *
 */
class HTMLPurifier_URIScheme_nntp extends HTMLPurifier_URIScheme {
  /**
   * @type int
   */
  public $default_port = 119;

  /**
   * @type bool
   */
  public $browsable = FALSE;

  /**
   * @param HTMLPurifier_URI $uri
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool
   */
  public function doValidate(&$uri, $config, $context) {

    $uri->userinfo = NULL;
    $uri->query = NULL;
    return TRUE;
  }

}

// vim: et sw=4 sts=4.
