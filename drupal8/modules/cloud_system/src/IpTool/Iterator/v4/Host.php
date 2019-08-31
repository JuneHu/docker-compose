<?php

namespace Drupal\cloud_system\IpTool\Iterator\v4;

use Drupal\cloud_system\IpTool\Address as IPAddr;

class Host extends Address {
  /**
   * @return IPAddr
   */
  public function current() {
    return $this->subnet[$this->hostIndex()];
  }

  /**
   * @return bool
   */
  public function valid() {
    $index = $this->hostIndex();

    // ignore broadcast address
    if (($this->subnet->numHosts() > 1) && ($index > $this->subnet->numHosts())) {
      return FALSE;
    }

    return isset($this->subnet[$index]);
  }

  protected function hostIndex() {
    // exclude network address except /32 subnets
    return ($this->subnet->numAddrs() > 1) ? $this->index + 1 : $this->index;
  }
}
