<?php

/**
 * @file
 * Concrete empty token class.
 */

/**
 *
 */
class HTMLPurifier_Token_Empty extends HTMLPurifier_Token_Tag {

  /**
   *
   */
  public function toNode() {
    $n = parent::toNode();
    $n->empty = TRUE;
    return $n;
  }

}

// vim: et sw=4 sts=4.
