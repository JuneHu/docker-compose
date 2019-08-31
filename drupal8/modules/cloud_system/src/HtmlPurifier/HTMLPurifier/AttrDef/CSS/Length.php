<?php

/**
 * @file
 * Represents a Length as defined by CSS.
 */

/**
 *
 */
class HTMLPurifier_AttrDef_CSS_Length extends HTMLPurifier_AttrDef {

  /**
   * @type HTMLPurifier_Length|string
   */
  protected $min;

  /**
   * @type HTMLPurifier_Length|string
   */
  protected $max;

  /**
   * @param HTMLPurifier_Length|string $min
   *   Minimum length, or null for no bound. String is also acceptable.
   * @param HTMLPurifier_Length|string $max
   *   Maximum length, or null for no bound. String is also acceptable.
   */
  public function __construct($min = NULL, $max = NULL) {

    $this->min = $min !== NULL ? HTMLPurifier_Length::make($min) : NULL;
    $this->max = $max !== NULL ? HTMLPurifier_Length::make($max) : NULL;
  }

  /**
   * @param string $string
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool|string
   */
  public function validate($string, $config, $context) {

    $string = $this->parseCDATA($string);

    // Optimizations.
    if ($string === '') {
      return FALSE;
    }
    if ($string === '0') {
      return '0';
    }
    if (strlen($string) === 1) {
      return FALSE;
    }

    $length = HTMLPurifier_Length::make($string);
    if (!$length->isValid()) {
      return FALSE;
    }

    if ($this->min) {
      $c = $length->compareTo($this->min);
      if ($c === FALSE) {
        return FALSE;
      }
      if ($c < 0) {
        return FALSE;
      }
    }
    if ($this->max) {
      $c = $length->compareTo($this->max);
      if ($c === FALSE) {
        return FALSE;
      }
      if ($c > 0) {
        return FALSE;
      }
    }
    return $length->toString();
  }

}

// vim: et sw=4 sts=4.
