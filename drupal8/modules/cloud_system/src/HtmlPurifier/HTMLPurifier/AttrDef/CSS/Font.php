<?php

/**
 * @file
 * Validates shorthand CSS property font.
 */

/**
 *
 */
class HTMLPurifier_AttrDef_CSS_Font extends HTMLPurifier_AttrDef {

  /**
   * Local copy of validators.
   *
   * @type HTMLPurifier_AttrDef[]
   *
   * @note If we moved specific CSS property definitions to their own
   *       classes instead of having them be assembled at run time by
   *       CSSDefinition, this wouldn't be necessary.  We'd instantiate
   *       our own copies.
   */
  protected $info = [];

  /**
   * @param HTMLPurifier_Config $config
   */
  public function __construct($config) {

    $def = $config->getCSSDefinition();
    $this->info['font-style'] = $def->info['font-style'];
    $this->info['font-variant'] = $def->info['font-variant'];
    $this->info['font-weight'] = $def->info['font-weight'];
    $this->info['font-size'] = $def->info['font-size'];
    $this->info['line-height'] = $def->info['line-height'];
    $this->info['font-family'] = $def->info['font-family'];
  }

  /**
   * @param string $string
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool|string
   */
  public function validate($string, $config, $context) {

    static $system_fonts = array(
      'caption' => TRUE,
      'icon' => TRUE,
      'menu' => TRUE,
      'message-box' => TRUE,
      'small-caption' => TRUE,
      'status-bar' => TRUE,
    );

    // Regular pre-processing.
    $string = $this->parseCDATA($string);
    if ($string === '') {
      return FALSE;
    }

    // Check if it's one of the keywords.
    $lowercase_string = strtolower($string);
    if (isset($system_fonts[$lowercase_string])) {
      return $lowercase_string;
    }

    // Bits to process.
    $bits = explode(' ', $string);
    // This indicates what we're looking for.
    $stage = 0;
    // Which stage 0 properties have we caught?
    $caught = [];
    $stage_1 = array('font-style', 'font-variant', 'font-weight');
    // Output.
    $final = '';

    for ($i = 0, $size = count($bits); $i < $size; $i++) {
      if ($bits[$i] === '') {
        continue;
      }
      switch ($stage) {
        // Attempting to catch font-style, font-variant or font-weight.
        case 0:
                    foreach ($stage_1 as $validator_name) {
            if (isset($caught[$validator_name])) {
              continue;
            }
            $r = $this->info[$validator_name]->validate(
                            $bits[$i],
                            $config,
                            $context
                        );
            if ($r !== FALSE) {
              $final .= $r . ' ';
              $caught[$validator_name] = TRUE;
              break;
            }
                    }
                    // All three caught, continue on.
                    if (count($caught) >= 3) {
                      $stage = 1;
                    }
                    if ($r !== FALSE) {
                      break;
                    }
                    // Attempting to catch font-size and perhaps line-height.
                  case 1:
                    $found_slash = FALSE;
                    if (strpos($bits[$i], '/') !== FALSE) {
                      list($font_size, $line_height) =
                            explode('/', $bits[$i]);
                      if ($line_height === '') {
                        // ooh, there's a space after the slash!
                        $line_height = FALSE;
                        $found_slash = TRUE;
                      }
                    }
                    else {
                      $font_size = $bits[$i];
                      $line_height = FALSE;
                    }
                    $r = $this->info['font-size']->validate(
                        $font_size,
                        $config,
                        $context
                    );
                    if ($r !== FALSE) {
                      $final .= $r;
                      // Attempt to catch line-height.
                      if ($line_height === FALSE) {
                        // We need to scroll forward.
                        for ($j = $i + 1; $j < $size; $j++) {
                          if ($bits[$j] === '') {
                            continue;
                          }
                          if ($bits[$j] === '/') {
                            if ($found_slash) {
                              return FALSE;
                            }
                            else {
                              $found_slash = TRUE;
                              continue;
                            }
                          }
                          $line_height = $bits[$j];
                          break;
                        }
                      }
                      else {
                        // Slash already found.
                        $found_slash = TRUE;
                        $j = $i;
                      }
                      if ($found_slash) {
                        $i = $j;
                        $r = $this->info['line-height']->validate(
                              $line_height,
                              $config,
                              $context
                          );
                        if ($r !== FALSE) {
                          $final .= '/' . $r;
                        }
                      }
                      $final .= ' ';
                      $stage = 2;
                      break;
                    }
          return FALSE;

        // Attempting to catch font-family.
        case 2:
                    $font_family =
                        implode(' ', array_slice($bits, $i, $size - $i));
          $r = $this->info['font-family']->validate(
                $font_family,
                $config,
                $context
            );
          if ($r !== FALSE) {
            $final .= $r . ' ';
            // Processing completed successfully.
            return rtrim($final);
          }
          return FALSE;
      }
    }
    return FALSE;
  }

}

// vim: et sw=4 sts=4.
