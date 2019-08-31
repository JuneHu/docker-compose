<?php

/**
 * @file
 * Validates an integer.
 *
 * @note While this class was modeled off the CSS definition, no currently
 *       allowed CSS uses this type.  The properties that do are: widows,
 *       orphans, z-index, counter-increment, counter-reset.  Some of the
 *       HTML attributes, however, find use for a non-negative version of this.
 */

/**
 *
 */
class HTMLPurifier_AttrDef_Integer extends HTMLPurifier_AttrDef {

  /**
   * Whether or not negative values are allowed.
   *
   * @type bool
   */
  protected $negative = TRUE;

  /**
   * Whether or not zero is allowed.
   *
   * @type bool
   */
  protected $zero = TRUE;

  /**
   * Whether or not positive values are allowed.
   *
   * @type bool
   */
  protected $positive = TRUE;

  /**
   * @param $negative Bool indicating whether or not negative values are allowed
   * @param $zero Bool indicating whether or not zero is allowed
   * @param $positive Bool indicating whether or not positive values are allowed
   */
  public function __construct($negative = TRUE, $zero = TRUE, $positive = TRUE) {

    $this->negative = $negative;
    $this->zero = $zero;
    $this->positive = $positive;
  }

  /**
   * @param string $integer
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool|string
   */
  public function validate($integer, $config, $context) {

    $integer = $this->parseCDATA($integer);
    if ($integer === '') {
      return FALSE;
    }

    // We could possibly simply typecast it to integer, but there are
    // certain fringe cases that must not return an integer.
    // Clip leading sign.
    if ($this->negative && $integer[0] === '-') {
      $digits = substr($integer, 1);
      if ($digits === '0') {
        $integer = '0';
      } // rm minus sign for zero
    }
    elseif ($this->positive && $integer[0] === '+') {
      // Rm unnecessary plus.
      $digits = $integer = substr($integer, 1);
    }
    else {
      $digits = $integer;
    }

    // Test if it's numeric.
    if (!ctype_digit($digits)) {
      return FALSE;
    }

    // Perform scope tests.
    if (!$this->zero && $integer == 0) {
      return FALSE;
    }
    if (!$this->positive && $integer > 0) {
      return FALSE;
    }
    if (!$this->negative && $integer < 0) {
      return FALSE;
    }

    return $integer;
  }

}

// vim: et sw=4 sts=4.
