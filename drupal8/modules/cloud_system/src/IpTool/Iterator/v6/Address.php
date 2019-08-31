<?php

namespace Drupal\cloud_system\IpTool\Iterator\v6;

use Drupal\cloud_system\IpTool\Address as IPAddr;

class Address implements \Iterator {
  /**
   * @var string decimal index representation for handling big v6 subnets
   */
  protected $index;

  /**
   * @var IPAddr
   */
  protected $subnet;

  public function __construct(IPAddr $subnet) {
    $this->index = '0';
    $this->subnet = $subnet;
  }

  /**
   * @return IPAddr
   */
  public function current() {
    return $this->subnet[$this->index];
  }

  /**
   * @return bool
   */
  public function valid() {
    return isset($this->subnet[$this->index]);
  }

  public function next() {
    $this->index = gmp_strval(gmp_add(gmp_init($this->index), 1), 10);
  }

  public function rewind() {
    $this->index = '0';
  }

  /**
   * @return string
   */
  public function key() {
    return gmp_strval($this->index, 10);
  }
}
