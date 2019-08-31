<?php

/**
 * @file
 */

/**
 *
 */
class HTMLPurifier_AttrDef_CSS_AlphaValue extends HTMLPurifier_AttrDef_CSS_Number {

  /**
   *
   */
  public function __construct() {

    // Opacity is non-negative, but we will clamp it.
    parent::__construct(FALSE);
  }

  /**
   * @param string $number
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return string
   */
  public function validate($number, $config, $context) {

    $result = parent::validate($number, $config, $context);
    if ($result === FALSE) {
      return $result;
    }
    $float = (float) $result;
    if ($float < 0.0) {
      $result = '0';
    }
    if ($float > 1.0) {
      $result = '1';
    }
    return $result;
  }

}

// vim: et sw=4 sts=4.
