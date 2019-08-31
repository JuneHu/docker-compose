<?php

/**
 * @file
 * Definition for tables.  The general idea is to extract out all of the
 * essential bits, and then reconstruct it later.
 *
 * This is a bit confusing, because the DTDs and the W3C
 * validators seem to disagree on the appropriate definition. The
 * DTD claims:
 *
 *      (CAPTION?, (COL*|COLGROUP*), THEAD?, TFOOT?, TBODY+)
 *
 * But actually, the HTML4 spec then has this to say:
 *
 *      The TBODY start tag is always required except when the table
 *      contains only one table body and no table head or foot sections.
 *      The TBODY end tag may always be safely omitted.
 *
 * So the DTD is kind of wrong.  The validator is, unfortunately, kind
 * of on crack.
 *
 * The definition changed again in XHTML1.1; and in my opinion, this
 * formulation makes the most sense.
 *
 *      caption?, ( col* | colgroup* ), (( thead?, tfoot?, tbody+ ) | ( tr+ ))
 *
 * Essentially, we have two modes: thead/tfoot/tbody mode, and tr mode.
 * If we encounter a thead, tfoot or tbody, we are placed in the former
 * mode, and we *must* wrap any stray tr segments with a tbody. But if
 * we don't run into any of them, just have tr tags is OK.
 */

/**
 *
 */
class HTMLPurifier_ChildDef_Table extends HTMLPurifier_ChildDef {
  /**
   * @type bool
   */
  public $allow_empty = FALSE;

  /**
   * @type string
   */
  public $type = 'table';

  /**
   * @type array
   */
  public $elements = array(
    'tr' => TRUE,
    'tbody' => TRUE,
    'thead' => TRUE,
    'tfoot' => TRUE,
    'caption' => TRUE,
    'colgroup' => TRUE,
    'col' => TRUE,
  );

  /**
   *
   */
  public function __construct() {

  }

  /**
   * @param array $children
   * @param HTMLPurifier_Config $config
   * @param HTMLPurifier_Context $context
   * @return array
   */
  public function validateChildren($children, $config, $context) {

    if (empty($children)) {
      return FALSE;
    }

    // Only one of these elements is allowed in a table.
    $caption = FALSE;
    $thead = FALSE;
    $tfoot = FALSE;

    // Whitespace.
    $initial_ws = [];
    $after_caption_ws = [];
    $after_thead_ws = [];
    $after_tfoot_ws = [];

    // As many of these as you want.
    $cols = [];
    $content = [];

    // If true, then we need to wrap any stray.
    $tbody_mode = FALSE;
    // <tr>s with a <tbody>.
    $ws_accum =& $initial_ws;

    foreach ($children as $node) {
      if ($node instanceof HTMLPurifier_Node_Comment) {
        $ws_accum[] = $node;
        continue;
      }
      switch ($node->name) {
        case 'tbody':
                $tbody_mode = TRUE;
          // Fall through.
        case 'tr':
                $content[] = $node;
          $ws_accum =& $content;
          break;

        case 'caption':
                // There can only be one caption!
                if ($caption !== FALSE) {
            break;
                }
                $caption = $node;
                $ws_accum =& $after_caption_ws;
          break;

        case 'thead':
                $tbody_mode = TRUE;
          // XXX This breaks rendering properties with
          // Firefox, which never floats a <thead> to
          // the top. Ever. (Our scheme will float the
          // first <thead> to the top.)  So maybe
          // <thead>s that are not first should be
          // turned into <tbody>? Very tricky, indeed.
          if ($thead === FALSE) {
            $thead = $node;
            $ws_accum =& $after_thead_ws;
          }
          else {
            // Oops, there's a second one! What
            // should we do?  Current behavior is to
            // transmutate the first and last entries into
            // tbody tags, and then put into content.
            // Maybe a better idea is to *attach
            // it* to the existing thead or tfoot?
            // We don't do this, because Firefox
            // doesn't float an extra tfoot to the
            // bottom like it does for the first one.
            $node->name = 'tbody';
            $content[] = $node;
            $ws_accum =& $content;
          }
          break;

        case 'tfoot':
                // See above for some aveats.
                $tbody_mode = TRUE;
          if ($tfoot === FALSE) {
            $tfoot = $node;
            $ws_accum =& $after_tfoot_ws;
          }
          else {
            $node->name = 'tbody';
            $content[] = $node;
            $ws_accum =& $content;
          }
          break;

        case 'colgroup':
            case 'col':
                $cols[] = $node;
          $ws_accum =& $cols;
          break;

        case '#PCDATA':
                // How is whitespace handled? We treat is as sticky to
                // the *end* of the previous element. So all of the
                // nonsense we have worked on is to keep things
                // together.
                if (!empty($node->is_whitespace)) {
            $ws_accum[] = $node;
                }
          break;
      }
    }

    if (empty($content)) {
      return FALSE;
    }

    $ret = $initial_ws;
    if ($caption !== FALSE) {
      $ret[] = $caption;
      $ret = array_merge($ret, $after_caption_ws);
    }
    if ($cols !== FALSE) {
      $ret = array_merge($ret, $cols);
    }
    if ($thead !== FALSE) {
      $ret[] = $thead;
      $ret = array_merge($ret, $after_thead_ws);
    }
    if ($tfoot !== FALSE) {
      $ret[] = $tfoot;
      $ret = array_merge($ret, $after_tfoot_ws);
    }

    if ($tbody_mode) {
      // We have to shuffle tr into tbody.
      $current_tr_tbody = NULL;

      foreach ($content as $node) {
        switch ($node->name) {
          case 'tbody':
                    $current_tr_tbody = NULL;
            $ret[] = $node;
            break;

          case 'tr':
                    if ($current_tr_tbody === NULL) {
              $current_tr_tbody = new HTMLPurifier_Node_Element('tbody');
              $ret[] = $current_tr_tbody;
                    }
                    $current_tr_tbody->children[] = $node;
            break;

          case '#PCDATA':
                    assert($node->is_whitespace);
            if ($current_tr_tbody === NULL) {
              $ret[] = $node;
            }
            else {
              $current_tr_tbody->children[] = $node;
            }
            break;
        }
      }
    }
    else {
      $ret = array_merge($ret, $content);
    }

    return $ret;

  }

}

// vim: et sw=4 sts=4.
