<?php

/**
 * @file
 * Takes the contents of blockquote when in strict and reformats for validation.
 */

/**
 *
 */
class HTMLPurifier_ChildDef_StrictBlockquote extends HTMLPurifier_ChildDef_Required {
  /**
   * @type array
   */
  protected $real_elements;

  /**
   * @type array
   */
  protected $fake_elements;

  /**
   * @type bool
   */
  public $allow_empty = TRUE;

  /**
   * @type string
   */
  public $type = 'strictblockquote';

  /**
   * @type bool
   */
  protected $init = FALSE;

  /**
   * @param HTMLPurifier_Config $config
   * @return array
   * @note We don't want MakeWellFormed to auto-close inline elements since
   *       they might be allowed.
   */
  public function getAllowedElements($config) {

    $this->init($config);
    return $this->fake_elements;
  }

  /**
   * @param array $children
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return array
   */
  public function validateChildren($children, $config, $context) {

    $this->init($config);

    // Trick the parent class into thinking it allows more.
    $this->elements = $this->fake_elements;
    $result = parent::validateChildren($children, $config, $context);
    $this->elements = $this->real_elements;

    if ($result === FALSE) {
      return [];
    }
    if ($result === TRUE) {
      $result = $children;
    }

    $def = $config->getHTMLDefinition();
    $block_wrap_name = $def->info_block_wrapper;
    $block_wrap = FALSE;
    $ret = [];

    foreach ($result as $node) {
      if ($block_wrap === FALSE) {
        if (($node instanceof HTMLPurifier_Node_Text && !$node->is_whitespace) ||
              ($node instanceof HTMLPurifier_Node_Element && !isset($this->elements[$node->name]))) {
          $block_wrap = new HTMLPurifier_Node_Element($def->info_block_wrapper);
          $ret[] = $block_wrap;
        }
      }
      else {
        if ($node instanceof HTMLPurifier_Node_Element && isset($this->elements[$node->name])) {
          $block_wrap = FALSE;

        }
      }
      if ($block_wrap) {
        $block_wrap->children[] = $node;
      }
      else {
        $ret[] = $node;
      }
    }
    return $ret;
  }

  /**
   * @param HTMLPurifier_Config $config
   */
  private function init($config) {

    if (!$this->init) {
      $def = $config->getHTMLDefinition();
      // Allow all inline elements.
      $this->real_elements = $this->elements;
      $this->fake_elements = $def->info_content_sets['Flow'];
      $this->fake_elements['#PCDATA'] = TRUE;
      $this->init = TRUE;
    }
  }

}

// vim: et sw=4 sts=4.
