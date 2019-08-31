<?php

/**
 * @file
 * Validates a Percentage as defined by the CSS spec.
 */

/**
 *
 */
class HTMLPurifier_AttrDef_CSS_Percentage extends HTMLPurifier_AttrDef {

  /**
   * Instance to defer number validation to.
   *
   * @type HTMLPurifier_AttrDef_CSS_Number
   */
  protected $number_def;

  /**
   * @param bool $non_negative
   *   Whether to forbid negative values
   */
  public function __construct($non_negative = FALSE) {

    $this->number_def = new HTMLPurifier_AttrDef_CSS_Number($non_negative);
  }

  /**
   * @param string $string
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool|string
   */
  public function validate($string, $config, $context) {

    $string = $this->parseCDATA($string);

    if ($string === '') {
      return FALSE;
    }
    $length = strlen($string);
    if ($length === 1) {
      return FALSE;
    }
    if ($string[$length - 1] !== '%') {
      return FALSE;
    }

    $number = substr($string, 0, $length - 1);
    $number = $this->number_def->validate($number, $config, $context);

    if ($number === FALSE) {
      return FALSE;
    }
    return "$number%";
  }

}

// vim: et sw=4 sts=4.
