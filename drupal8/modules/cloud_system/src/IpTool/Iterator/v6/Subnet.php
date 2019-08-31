<?php

namespace Drupal\cloud_system\IpTool\Iterator\v6;

use Drupal\cloud_system\IpTool\Iterator\SubnetTrait;

class Subnet extends Address {
  use SubnetTrait;

  /**
   * @return string
   */
  protected function subnetIndex() {
    return gmp_strval(gmp_mul($this->index, $this->numAddrs), 10);
  }
}
