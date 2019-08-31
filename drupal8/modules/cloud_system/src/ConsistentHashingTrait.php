<?php

namespace Drupal\cloud_system;

/**
 * Php consistent hashing.
 */
trait ConsistentHashingTrait {

  private $_node = [];

  /**
   * Hashes the given string into a 32bit address space.
   *
   * @param string $str
   *
   * @return mixed
   *  A sortable format with 0xFFFFFFFF possible values.
   */
  public function _hash($str) {
    return sprintf('%u', crc32($str));
  }

  /**
   * add a node.
   *
   * @param string $node
   * @param int $index
   */
  public function addNodes($node, $index = 0) {
    $index = intval($index) ? intval($index) : 64;
    for ($i = 0; $i < $index; $i++) {
      $this->_node[$this->_hash($node . $i)] = $node;
    }
    $this->_sortNode();
  }

  /**
   * Looks up the target for the given resource.
   *
   * @param string $key
   *
   * @return string
   */
  public function lookup($key) {
    $hash = $this->_hash($key);
    $node = current($this->_node);
    foreach ($this->_node as $k => $v) {
      if ($hash <= $k) {
        return $v;
      }
    }
    return $node;
  }

  /**
   * Remove a node.
   *
   * @param string $node
   */
  public function removeNode($node) {
    foreach ($this->_node as $k => $v) {
      if ($v == $node) {
        unset($this->_node[$k]);
      }
    }
  }

  /**
   * Sorts the internal mapping (positions to nodes) by position
   */
  private function _sortNode() {
    $this->_node && ksort($this->_node);
  }
}