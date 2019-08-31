<?php

/**
 * @file
 * VERY RELAXED! Shouldn't cause problems, not even Firefox checks if the.
 */

// Email is valid, but be careful!
/**
 * Validates mailto (for E-mail) according to RFC 2368.
 *
 * @todo Validate the email address
 * @todo Filter allowed query parameters
 */
class HTMLPurifier_URIScheme_mailto extends HTMLPurifier_URIScheme {
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
    $uri->host     = NULL;
    $uri->port     = NULL;
    // We need to validate path against RFC 2368's addr-spec.
    return TRUE;
  }

}

// vim: et sw=4 sts=4.
