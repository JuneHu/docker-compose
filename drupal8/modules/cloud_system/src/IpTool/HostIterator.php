<?php

namespace Drupal\cloud_system\IpTool;

class HostIterator extends AddressIterator {
  public function __construct(Address $subnet) {
    parent::__construct($subnet);

    // overwrite iterator object
    $iteratorClassName = sprintf('\BIS\IPAddr\Iterator\v%d\Host', $subnet->version());
    if (class_exists($iteratorClassName)) {
      $this->iterator = new $iteratorClassName($subnet);
    }
    else {
      throw new \InvalidArgumentException('Unimplemented iterator for given version');
    }
  }
}
