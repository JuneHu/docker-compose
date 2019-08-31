<?php

namespace Drupal\cloud_system;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CloudSystemRedisManager {

  /**
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * @var string
   */
  private $settingKey;

  /**
   * Construct the statistics storage.
   *
   * @param \Drupal\Core\Site\Settings $settings
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Component\Datetime\TimeInterface $time
   */
  public function __construct(Settings $settings) {
    if (!extension_loaded('redis')) {
      throw new HttpException(400, 'Missing redis.');
    }
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient($setting_key = '') {
    $this->settingKey = !empty($setting_key) ? $setting_key : 'redis';

    $redis_settings = $this->settings->get($this->settingKey);
    if (empty($redis_settings)) {
      throw new HttpException(400, 'No redis settings.');
    }

    $redis = new \Redis;
    $parameters = array(
      array_key_exists('server', $redis_settings)
        ? $redis_settings['server']
        : 'localhost',
      array_key_exists('port', $redis_settings)
        ? $redis_settings['port']
        : NULL,
      array_key_exists('timeout', $redis_settings)
        ? $redis_settings['timeout']
        : NULL,
    );

    $persist = 'connect';
    $parameters[] = array_key_exists('retry', $redis_settings)
      ? $redis_settings['retry']
      : NULL;
    call_user_func_array(array($redis, $persist), $parameters);
    if (array_key_exists('password', $redis_settings)) {
      $redis->auth($redis_settings['password']);
    }
    if (isset($redis_settings['database'])) {
      $redis->select($redis_settings['database']);
    }
    return $redis;
  }

}
