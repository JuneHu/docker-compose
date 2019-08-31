<?php

namespace Drupal\cloud_system\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * A smtp worker.
 *
 * @QueueWorker(
 *   id = "smtp_queue",
 *   title = @Translation("smtp queue worker"),
 *   cron = {"time" = 60}
 * )
 */
class SmtpQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->queueRunner($data);
  }

  /**
   * {@inheritdoc}
   */
  protected function queueRunner($variables) {
    $mail = $variables['to'];
    $variables['isQueue'] = 0;

    $config = \Drupal::configFactory()->getEditable('cloud_system.email_config');
    $variables['conf'] = [
      'smtp_server' => $config->get('smtp_host'),
      'smtp_user' => $config->get('mail'),
      'smtp_pwd' => $config->get('smtp_pass'),
      'smtp_port' => $config->get('smtp_port'),
      'is_ssl' => FALSE,
      'logo' => $config->get('logo'),
    ];
    //\Drupal::logger('smtp_queue' . $mail)->notice('<pre>' . print_r($variables, true) . '</pre>');
    return \Drupal::service('cloud_system.base')->sendMail($mail, $variables);
  }
}
