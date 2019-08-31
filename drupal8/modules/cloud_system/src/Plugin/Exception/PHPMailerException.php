<?php

namespace Drupal\cloud_system\Plugin\Exception;

/**
 * Class PHPMailerException.
 *
 * @package Drupal\cloud_system\Plugin\Exception
 */
class PHPMailerException extends \Exception {

  /**
   * The exception handle.
   *
   * @return string
   *   Error message.
   */
  public function errorMessage() {
    $errorMsg = '<strong>' . $this->getMessage() . "</strong><br />\n";
    return $errorMsg;
  }

}
