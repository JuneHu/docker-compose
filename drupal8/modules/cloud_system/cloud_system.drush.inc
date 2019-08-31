<?php

/**
 * Implements hook_drush_command().
 */
function cloud_system_drush_command() {
  $items = array();

  $items['cloud'] = array(
    'description' => "融合云drush命令.",
    'arguments' => array(
      'type' => '命令种类，取值[poup].',
    ),
    'callback' => 'drush_cloud_system_distribute',
    'examples' => array(
      'drush cloud --help' => '输出用法.',
      'drush cloud poup' => '更新po文件，保存到redis中.',
    ),
    'aliases' => array('cloud'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function cloud_system_drush_help($section) {
  switch ($section) {
    case 'drush:cloud':
      return dt("融合云drush命令.");
  }
}

/**
 * 根据类型分发数据.
 */
function drush_cloud_system_distribute() {
  // Get the arguments from CLI.
  $args = func_get_args();
  $arg1 = 0;
  if (!empty($args[0])) {
    $arg1 = $args[0];
  }

  if (empty($arg1)) {
    drushLog('缺少类型参数，用法：drush cloud --help', 'error');
    return FALSE;
  }

  $message = "正在更新：获取到的参数，类型：" . $arg1;
  drushLog($message);

  if (drush_confirm('确认继续?')) {
    call_user_func_array('drush_cloud_system_' . $arg1 . '_command', [
      'clear' => 0
    ]);
  }
}
