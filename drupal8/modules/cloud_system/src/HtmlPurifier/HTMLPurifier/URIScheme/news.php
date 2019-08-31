<?php

/**
 * @file
 * Validates news (Usenet) as defined by generic RFC 1738.
 */

/**
 *
 */
class HTMLPurifier_URIScheme_news extends HTMLPurifier_URIScheme {
  /**
   * @type bool
   */
  public $browsable = FALSE;

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

    $uri->userinfo = NULL;
    $uri->host = NULL;
    $uri->port = NULL;
    $uri->query = NULL;
    // Typecode check needed on path.
    return TRUE;
  }

}

// vim: et sw=4 sts=4.
