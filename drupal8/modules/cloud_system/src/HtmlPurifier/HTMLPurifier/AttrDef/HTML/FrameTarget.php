<?php

/**
 * @file
 * Special-case enum attribute definition that lazy loads allowed frame targets.
 */

/**
 *
 */
class HTMLPurifier_AttrDef_HTML_FrameTarget extends HTMLPurifier_AttrDef_Enum {

  /**
   * @type array
   */
  // Uninitialized value.
  public $valid_values = FALSE;

  /**
   * @type bool
   */
  protected $case_sensitive = FALSE;

  /**
   *
   */
  public function __construct() {

  }

  /**
   * @param string $string
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return bool|string
   */
  public function validate($string, $config, $context) {

    if ($this->valid_values === FALSE) {
      $this->valid_values = $config->get('Attr.AllowedFrameTargets');
    }
    return parent::validate($string, $config, $context);
  }

}

// vim: et sw=4 sts=4.
