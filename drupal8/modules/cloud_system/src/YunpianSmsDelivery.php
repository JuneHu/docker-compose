<?php

namespace Drupal\cloud_system;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\cloud_system\SmsDeliveryInterface;

/**
 * Provides yunpian sms builder.
 */
class YunpianSmsDelivery implements SmsDeliveryInterface {
  use StringTranslationTrait;

  /**
   * Indicates the yunpian sms api key.
   */
  const APIKEY = '9fe62c2a4ff30ab11bc8c14dcaf92113';

  /**
   * Indicates the yunpian api url.
   */
  const APIURL = 'https://sms.yunpian.com/v2';

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The system base.
   */
  protected $base;

  /**
   * Construct the request.
   */
  public function __construct() {
    $this->base = \Drupal::service('cloud_system.base');
  }

  /**
   * @see https://www.yunpian.com/api2.0/user.html#a1.
   */
  public function getUserInfo() {
    try {
      $options = [
        'method' => 'POST',
        'accept' => 'text/plain;charset=utf-8',
        'content-type' => 'application/x-www-form-urlencoded;charset=utf-8',
        'needError' => 1,
        'body' => [
          'apikey' => self::APIKEY,
        ],
      ];

      $return = $this->base->httpRequest(self::APIURL . '/user/get.json', $options);
      $return = json_decode($return);
      if (isset($return->code) && $return->code != 0) {
        $msg = !empty($return->msg) ? $return->msg : $return->detail;
        throw new HttpException(400, $msg);
      }
    } catch (\Exception $e) {
      throw $e;
    }

    return $return;
  }

  /**
   * @see https://www.yunpian.com/api2.0/sms.html#c1.
   */
  public function send($recipient, $body, $originator = '') {
    try {
      $options = [
        'method' => 'POST',
        'accept' => 'text/plain;charset=utf-8',
        'content-type' => 'application/x-www-form-urlencoded;charset=utf-8',
        'needError' => 1,
        'body' => [
          'apikey' => self::APIKEY,
          'mobile' => $recipient,
          'text' => $body,
        ],
      ];

      $return = $this->base->httpRequest(self::APIURL . '/sms/single_send.json', $options);
      $return = json_decode($return);
      if (isset($return->code) && $return->code != 0) {
        $msg = !empty($return->msg) ? $return->msg : $return->detail;
        throw new HttpException(400, $msg);
      }
    } catch (\Exception $e) {
      throw $e;
    }
    return $return;
  }

  /**
   * @see https://www.yunpian.com/api2.0/sms.html#c2.
   */
  public function batchSend($recipients, $body, $originator = '') {
    try {
      $options = [
        'method' => 'POST',
        'accept' => 'text/plain;charset=utf-8',
        'content-type' => 'application/x-www-form-urlencoded;charset=utf-8',
        'needError' => 1,
        'body' => [
          'apikey' => self::APIKEY,
          'mobile' => $recipients,
          'text' => $body,
        ],
      ];

      $return = $this->base->httpRequest(self::APIURL . '/sms/batch_send.json', $options);
      $return = json_decode($return);
      if (isset($return->code) && $return->code != 0) {
        $msg = !empty($return->msg) ? $return->msg : $return->detail;
        throw new HttpException(400, $msg);
      }
    } catch (\Exception $e) {
      throw $e;
    }

    return $return;
  }

  /**
   * @see https://www.yunpian.com/api2.0/sms.html#c9.
   */
  public function getRecord($data) {
    $mobile = $data['phone'] ?? '';
    $startTime = $data['startTime'] ?? date('Y-m-d', time() - 8600 * 7) . ' 00:00:00';
    $endTime = $data['endTime'] ?? date('Y-m-d G:i:s', time());
    $body = [
      'apikey' => self::APIKEY,
      'start_time' => $startTime,
      'end_time' => $endTime,
      'page' => $data['page'] ?? 1,
      'page_size' => 100,
    ];

    if ($mobile) {
      $body['mobile'] = $mobile;
    }

    try {
      $options = [
        'method' => 'POST',
        'accept' => 'text/plain;charset=utf-8',
        'content-type' => 'application/x-www-form-urlencoded;charset=utf-8',
        'needError' => 1,
        'body' => $body,
      ];

      $return = $this->base->httpRequest(self::APIURL . '/sms/get_record.json', $options);
      $return = json_decode($return);
      if (isset($return->code) && $return->code != 0) {
        $msg = !empty($return->msg) ? $return->msg : $return->detail;
        throw new HttpException(400, $msg);
      }
    } catch (\Exception $e) {
      throw $e;
    }
    return $return;
  }

}
