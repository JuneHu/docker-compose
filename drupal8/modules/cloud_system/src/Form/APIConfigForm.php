<?php

namespace Drupal\cloud_system\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure API Path.
 */
class APIConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cloud_system.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'cloud_system.api.settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cloud_system.settings');
    $portal_api = $config->get('portal_api') ?: '';
    $agent_api = $config->get('agent_api') ?: '';
    $common_api = $config->get('common_api') ?: '';
    $token_expires_in = $config->get('token_expires_in') ?: 300;
    $crypt_salt = $config->get('crypt_salt') ?: 'verycloud#cryptpass';
    $ignore_apis = $config->get('ignore_apis') ?: '';
    $not_check_api = [];
    if (!empty($ignore_apis)) {
      foreach ($ignore_apis as $api) {
        $not_check_api[] = $api['uri'] . ',' . $api['method'];
      }
    }

    $dblog_enable = $config->get('dblog_enable') ?: 0;

    $workflow_notice_mail = $config->get('workflow_notice_mail') ?: '';
    $workflow_notice_sms = $config->get('workflow_notice_sms') ?: '';
    $workflow_todo_list = $config->get('workflow_todo_list') ?: '';

    $form['api_settings'] = [
      '#type' => 'details',
      '#title' => 'API设置',
      '#open' => TRUE,
    ];
    $form['api_settings']['is_api_service'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('校验Authorization, If enable, Require header Authorization'),
      '#default_value' => $config->get('is_api_service'),
    ];
    $form['api_settings']['portal_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Portal API地址'),
      '#default_value' => $portal_api,
    ];
    $form['api_settings']['agent_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('代理平台API地址'),
      '#default_value' => $agent_api,
    ];
    $form['api_settings']['common_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('通用后端API地址'),
      '#default_value' => $common_api,
    ];
    $form['api_settings']['crypt_salt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('encrypt salt'),
      '#default_value' => $crypt_salt,
    ];
    $form['api_settings']['dblog_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('打印http请求日志'),
      '#default_value' => $dblog_enable,
    ];

    $form['api_expire_setting'] = [
      '#type' => 'details',
      '#title' => 'API超时设置',
      '#open' => TRUE,
    ];
    $form['api_expire_setting']['token_expires_in'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token超时时间'),
      '#default_value' => $token_expires_in,
    ];

    $form['api_access_setting'] = [
      '#type' => 'details',
      '#title' => 'API权限验证',
      '#open' => TRUE,
    ];

    $form['api_access_setting']['api_permission'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('启用了之后,会针对URL做访问权限校验'),
      '#default_value' => $config->get('api_permission'),
    ];

    $form['not_check_access_role'] = [
      '#type' => 'details',
      '#title' => '忽略认证API列表',
      '#open' => TRUE,
    ];
    $form['not_check_access_role']['ignore_apis'] = [
      '#type' => 'textarea',
      '#title' => $this->t('格式URL,METHOD'),
      '#default_value' => join(PHP_EOL, $not_check_api),
    ];

    $form['workflow_details'] = [
      '#type' => 'details',
      '#title' => '工作流配置',
      '#open' => TRUE,
    ];
    $form['workflow_details']['workflow_notice_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('发送mail'),
      '#default_value' => $workflow_notice_mail,
    ];
    $form['workflow_details']['workflow_notice_sms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('发送短信'),
      '#default_value' => $workflow_notice_sms,
    ];
    $form['workflow_details']['workflow_todo_list'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TODO列表地址'),
      '#default_value' => $workflow_todo_list,
    ];

    $form['access_control'] = [
      '#type' => 'details',
      '#title' => '访问控制',
      '#open' => TRUE
    ];

    $form['access_control']['ip_whitelist'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('IP白名单，启用之后将会根据系统参数中配置的IP白名单进行访问限制，<a href="/sys/conf" target="_blank">查看IP白名单</a>'),
      '#default_value' => $config->get('ip_whitelist')
    ];

    $form['platform_set'] = [
      '#type' => 'details',
      '#title' => '平台设置',
      '#open' => TRUE,
    ];

    $form['platform_set']['platform'] = [
      '#type' => 'radios',
      '#title' => "选择平台",
      '#description' => '设置了之后，将会在http request里增加参数如is_boss, is_portal, is_dev',
      '#options' => $this->availablePlatFormOptions(),
      '#required' => TRUE,
      '#default_value' => $config->get('platform'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  private function availablePlatFormOptions() {
    return [
      'is_boss' => 'BOSS',
      'is_portal' => 'PORTAL',
      'is_api' => 'API',
      'is_dev' => '开发测试平台',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ignore_apis = $form_state->getValue('ignore_apis');
    $apis = explode(PHP_EOL, $ignore_apis);
    $api_array = $ignore_routes = [];
    if (!empty($apis)) {
      foreach ($apis as $api) {
        $api_url = explode(',', $api);
        $api_array[] = [
          'uri' => $api_url[0],
          'method' => $api_url[1],
        ];

        $url = str_replace('.', '_', $api_url[0]);
        $ignore_routes[$url] = $api_url[1];
      }
    }

    $this->config('cloud_system.settings')
      ->set('is_api_service', $form_state->getValue('is_api_service'))
      ->set('token_expires_in', $form_state->getValue('token_expires_in'))
      ->set('crypt_salt', $form_state->getValue('crypt_salt'))
      ->set('dblog_enable', $form_state->getValue('dblog_enable'))
      ->set('portal_api', $form_state->getValue('portal_api'))
      ->set('agent_api', $form_state->getValue('agent_api'))
      ->set('common_api', $form_state->getValue('common_api'))
      ->set('api_permission', $form_state->getValue('api_permission'))
      ->set('ignore_apis', $api_array)
      ->set('ignore_routes', $ignore_routes)
      ->set('workflow_notice_mail', $form_state->getValue('workflow_notice_mail'))
      ->set('workflow_notice_sms', $form_state->getValue('workflow_notice_sms'))
      ->set('workflow_todo_list', $form_state->getValue('workflow_todo_list'))
      ->set('ip_whitelist', $form_state->getValue('ip_whitelist'))
      ->set('platform', $form_state->getValue('platform'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
