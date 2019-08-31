<?php

/**
 * @file W3C says:
 * [ // adjective and number must be in correct order, even if
 * // you could switch them without introducing ambiguity.
 * // some browsers support that syntax
 * [
 * <percentage> | <length> | left | center | right
 * ]
 * [
 * <percentage> | <length> | top | center | bottom
 * ]?
 * ] |
 * [ // this signifies that the vertical and horizontal adjectives
 * // can be arbitrarily ordered, however, there can only be two,
 * // one of each, or none at all
 * [
 * left | center | right
 * ] ||
 * [
 * top | center | bottom
 * ]
 * ]
 * top, left = 0%
 * center, (none) = 50%
 * bottom, right = 100%.
 */

/* QuirksMode says:
    keyword + length/percentage must be ordered correctly, as per W3C

    Internet Explorer and Opera, however, support arbitrary ordering. We
    should fix it up.

    Minor issue though, not strictly necessary.
 */

// Control freaks may appreciate the ability to convert these to
// percentages or something, but it's not necessary.
/**
 * Validates the value of background-position.
 */
class HTMLPurifier_AttrDef_CSS_BackgroundPosition extends HTMLPurifier_AttrDef {

  /**
   * @type HTMLPurifier_AttrDef_CSS_Length
   */
  protected $length;

  /**
   * @type HTMLPurifier_AttrDef_CSS_Percentage
   */
  protected $percentage;

  /**
   *
   */
  public function __construct() {

    $this->length = new HTMLPurifier_AttrDef_CSS_Length();
    $this->percentage = new HTMLPurifier_AttrDef_CSS_Percentage();
  }

  /**
   * @param string $string
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool|string
   */
  public function validate($string, $config, $context) {

    $string = $this->parseCDATA($string);
    $bits = explode(' ', $string);

    $keywords = [];
    // left, right.
    $keywords['h'] = FALSE;
    // top, bottom.
    $keywords['v'] = FALSE;
    // Center (first word)
    $keywords['ch'] = FALSE;
    // Center (second word)
    $keywords['cv'] = FALSE;
    $measures = [];

    $i = 0;

    $lookup = array(
      'top' => 'v',
      'bottom' => 'v',
      'left' => 'h',
      'right' => 'h',
      'center' => 'c',
    );

    foreach ($bits as $bit) {
      if ($bit === '') {
        continue;
      }

      // Test for keyword.
      $lbit = ctype_lower($bit) ? $bit : strtolower($bit);
      if (isset($lookup[$lbit])) {
        $status = $lookup[$lbit];
        if ($status == 'c') {
          if ($i == 0) {
            $status = 'ch';
          }
          else {
            $status = 'cv';
          }
        }
        $keywords[$status] = $lbit;
        $i++;
      }

      // Test for length.
      $r = $this->length->validate($bit, $config, $context);
      if ($r !== FALSE) {
        $measures[] = $r;
        $i++;
      }

      // Test for percentage.
      $r = $this->percentage->validate($bit, $config, $context);
      if ($r !== FALSE) {
        $measures[] = $r;
        $i++;
      }
    }

    if (!$i) {
      return FALSE;
    } // no valid values were caught

    $ret = [];

    // First keyword.
    if ($keywords['h']) {
      $ret[] = $keywords['h'];
    }
    elseif ($keywords['ch']) {
      $ret[] = $keywords['ch'];
      // Prevent re-use: center = center center.
      $keywords['cv'] = FALSE;
    }
    elseif (count($measures)) {
      $ret[] = array_shift($measures);
    }

    if ($keywords['v']) {
      $ret[] = $keywords['v'];
    }
    elseif ($keywords['cv']) {
      $ret[] = $keywords['cv'];
    }
    elseif (count($measures)) {
      $ret[] = array_shift($measures);
    }

    if (empty($ret)) {
      return FALSE;
    }
    return implode(' ', $ret);
  }

}

// vim: et sw=4 sts=4.
