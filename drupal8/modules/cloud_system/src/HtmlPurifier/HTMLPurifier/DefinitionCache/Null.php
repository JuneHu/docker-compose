<?php

/**
 * @file
 * Null cache object to use when no caching is on.
 */

/**
 *
 */
class HTMLPurifier_DefinitionCache_Null extends HTMLPurifier_DefinitionCache {

  /**
   * @param HTMLPurifier_Definition $def
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function add($def, $config) {

    return FALSE;
  }

  /**
   * @param HTMLPurifier_Definition $def
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function set($def, $config) {

    return FALSE;
  }

  /**
   * @param HTMLPurifier_Definition $def
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function replace($def, $config) {

    return FALSE;
  }

  /**
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function remove($config) {

    return FALSE;
  }

  /**
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function get($config) {

    return FALSE;
  }

  /**
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function flush($config) {

    return FALSE;
  }

  /**
   * @param HTMLPurifier_Config $config
   * @return bool
   */
  public function cleanup($config) {

    return FALSE;
  }

}

// vim: et sw=4 sts=4.
