<?php

namespace Drupal\cloud_system\IpTool\Iterator\v4;

use Drupal\cloud_system\IpTool\Iterator\SubnetTrait;

class Subnet extends Address {
  use SubnetTrait;

  /**
   * @return int
   */
  protected function subnetIndex() {
    return $this->index * $this->numAddrs;
  }
}
