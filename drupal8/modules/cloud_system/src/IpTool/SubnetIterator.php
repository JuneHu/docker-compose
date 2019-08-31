<?php

namespace Drupal\cloud_system\IpTool;

/**
 * Class SubnetIterator
 * @package Drupal\cloud_system\IpTool
 */
class SubnetIterator extends AddressIterator {
  /**
   * @param Address $divisibleSubnet
   * @param int $dividerPrefixLength
   */
  public function __construct(Address $divisibleSubnet, $dividerPrefixLength) {
    parent::__construct($divisibleSubnet);

    $iteratorClassName = sprintf('\BIS\IPAddr\Iterator\v%d\Subnet', $divisibleSubnet->version());
    if (class_exists($iteratorClassName)) {
      $this->iterator = new $iteratorClassName($divisibleSubnet, $dividerPrefixLength);
    }
    else {
      throw new \InvalidArgumentException('Unimplemented iterator for given version');
    }

    $this->iterator->setDividerPrefixLength(intval($dividerPrefixLength));
  }
}
