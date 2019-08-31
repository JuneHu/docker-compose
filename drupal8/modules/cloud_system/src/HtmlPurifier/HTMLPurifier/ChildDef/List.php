<?php

/**
 * @file
 * Definition for list containers ul and ol.
 *
 * What does this do?  The big thing is to handle ol/ul at the top
 * level of list nodes, which should be handled specially by /folding/
 * them into the previous list node.  We generally shouldn't ever
 * see other disallowed elements, because the autoclose behavior
 * in MakeWellFormed handles it.
 */

/**
 *
 */
class HTMLPurifier_ChildDef_List extends HTMLPurifier_ChildDef {
  /**
   * @type string
   */
  public $type = 'list';
  /**
   * @type array
   */
  // Lying a little bit, so that we can handle ul and ol ourselves
  // XXX: This whole business with 'wrap' is all a bit unsatisfactory.
  public $elements = array('li' => TRUE, 'ul' => TRUE, 'ol' => TRUE);

  /**
   * @param array $children
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return array
   */
  public function validateChildren($children, $config, $context) {

    // Flag for subclasses.
    $this->whitespace = FALSE;

    // If there are no tokens, delete parent node.
    if (empty($children)) {
      return FALSE;
    }

    // If li is not allowed, delete parent node.
    if (!isset($config->getHTMLDefinition()->info['li'])) {
      trigger_error("Cannot allow ul/ol without allowing li", E_USER_WARNING);
      return FALSE;
    }

    // The new set of children.
    $result = [];

    // A little sanity check to make sure it's not ALL whitespace.
    $all_whitespace = TRUE;

    $current_li = FALSE;

    foreach ($children as $node) {
      if (!empty($node->is_whitespace)) {
        $result[] = $node;
        continue;
      }
      // phew, we're not talking about whitespace.
      $all_whitespace = FALSE;

      if ($node->name === 'li') {
        // Good.
        $current_li = $node;
        $result[] = $node;
      }
      else {
        // We want to tuck this into the previous li
        // Invariant: we expect the node to be ol/ul
        // ToDo: Make this more robust in the case of not ol/ul
        // by distinguishing between existing li and li created
        // to handle non-list elements; non-list elements should
        // not be appended to an existing li; only li created
        // for non-list. This distinction is not currently made.
        if ($current_li === FALSE) {
          $current_li = new HTMLPurifier_Node_Element('li');
          $result[] = $current_li;
        }
        $current_li->children[] = $node;
        // XXX fascinating! Check for this error elsewhere ToDo.
        $current_li->empty = FALSE;
      }
    }
    if (empty($result)) {
      return FALSE;
    }
    if ($all_whitespace) {
      return FALSE;
    }
    return $result;
  }

}

// vim: et sw=4 sts=4.
