<?php

/**
 * @file
 * Validates http (HyperText Transfer Protocol) as defined by RFC 2616.
 */

/**
 *
 */
class HTMLPurifier_URIScheme_http extends HTMLPurifier_URIScheme {
  /**
   * @type int
   */
  public $default_port = 80;

  /**
   * @type bool
   */
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

    $uri->userinfo = NULL;
    return TRUE;
  }

}

// vim: et sw=4 sts=4.
