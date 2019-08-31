<?php

namespace Drupal\cloud_system\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure email.
 */
class EmailConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cloud_system.email_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'cloud_system.email.settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cloud_system.email_config');

    $xmailer = $config->get('xmailer') ?: 'www.verycloud.cn';
    $mail = $config->get('mail') ?: 'services@verycloud.cn';
    $from_name = $config->get('from_name') ?: 'VeryCloud云端网络 一站式云计算综合服务提供商';
    $logo = $config->get('logo') ?: 'https://static.verycloud.cn/profiles/verycloud/themes/miveus/images/logo1.png';
    $username = $config->get('username') ?: 'Developer';
    $platform = $config->get('platform') ?: 'VeryCloud';
    $subject = $config->get('subject') ?: 'VeryCloud提示信息';
    $needContactInfo = $config->get('needContactInfo') ?: 0;
    $default_mail = $config->get('default_mail') ?: 'developer@verycloud.cn';
    $smtp_host = $config->get('smtp_host') ?: 'mail.verycloud.cn';
    $smtp_pass = $config->get('smtp_pass') ?: 'nicaiba_88';
    $smtp_port = $config->get('smtp_port') ?: 25;
    $ssl_protocol = $config->get('ssl_protocol') ?: '';


    $form['email_settings'] = [
      '#type' => 'details',
      '#title' => '邮件配置',
      '#open' => TRUE,
    ];

    $form['email_settings']['smtp_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMTP HOST'),
      '#default_value' => $smtp_host,
    ];

    $form['email_settings']['smtp_pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMTP PASSWORD'),
      '#default_value' => $smtp_pass,
    ];

    $form['email_settings']['smtp_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMTP PORT'),
      '#default_value' => $smtp_port,
    ];

    $form['email_settings']['ssl_protocol'] = [
      '#type' => 'radios',
      '#title' => "SSL协议",
      '#description' => 'SSL协议',
      '#options' => $this->getSslProtocol(),
      '#default_value' => $config->get('ssl_protocol'),
    ];

    $form['email_settings']['xmailer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('X-Mailer，如www.baidu.com'),
      '#default_value' => $xmailer,
    ];
    $form['email_settings']['mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('发件邮箱地址'),
      '#default_value' => $mail,
    ];
    $form['email_settings']['from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('发件名称'),
      '#default_value' => $from_name,
    ];
    $form['email_settings']['logo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LOGO'),
      '#default_value' => $logo,
    ];
    $form['email_settings']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('发件用户名'),
      '#default_value' => $username,
    ];
    $form['email_settings']['platform'] = [
      '#type' => 'textfield',
      '#title' => $this->t('发件平台名称'),
      '#default_value' => $platform,
    ];
    $form['email_settings']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('默认发件标题'),
      '#default_value' => $subject,
    ];

    $form['email_settings']['needContactInfo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('是否需要联系信息'),
      '#default_value' => $needContactInfo,
    ];

    $form['email_settings']['default_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('默认developer收件地址'),
      '#default_value' => $default_mail,
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  private function getSslProtocol() {
    return [
      '' => '空',
      'ssl' => 'SSL',
      'tls' => 'TLS',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('cloud_system.email_config')
      ->set('smtp_host', $form_state->getValue('smtp_host'))
      ->set('smtp_pass', $form_state->getValue('smtp_pass'))
      ->set('smtp_port', $form_state->getValue('smtp_port'))
      ->set('ssl_protocol', $form_state->getValue('ssl_protocol'))
      ->set('xmailer', $form_state->getValue('xmailer'))
      ->set('mail', $form_state->getValue('mail'))
      ->set('from_name', $form_state->getValue('from_name'))
      ->set('logo', $form_state->getValue('logo'))
      ->set('username', $form_state->getValue('username'))
      ->set('platform', $form_state->getValue('platform'))
      ->set('subject', $form_state->getValue('subject'))
      ->set('needContactInfo', $form_state->getValue('needContactInfo'))
      ->set('default_mail', $form_state->getValue('default_mail'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
