<?php
/**
 * @file
 * Contains \Drupal\cloud_system\SmsDeliveryInterface.
 */

namespace Drupal\cloud_system;

/**
 * Provides an interface to sms delivery.
 *
 * @package Drupal\cloud_system
 */
interface SmsDeliveryInterface {

  /**
   * Send a message to the given phone number.
   *
   * @param string $recipient
   *   The phone number.
   * @param string $body
   *   The message to send.
   * @param string $originator
   *   The name of the person which sends the message.
   *
   * @return \Drupal\cloud_system\SmsDeliveryInterface
   *   A Sms result object.
   */
  public function send($recipient, $body, $originator = '');

  /**
   * Sends A batched messages to the given phone number.
   *
   * @param string $recipients
   *   The phone number.
   * @param string $body
   *   The message to send.
   * @param string $originator
   *   The name of the person which sends the message.
   *
   * @return \Drupal\cloud_system\SmsDeliveryInterface
   *   A Sms result object.
   */
  public function batchSend($recipients, $body, $originator = '');
}
