<?php

use Symfony\Component\Translation\Loader\PoFileLoader;

/**
 * Implements hook_cloud_command().
 */
function drush_cloud_system_poup_command($clear = 0) {
  $beginTime = microtime(TRUE);
  drushLog("开始导入po文件");

  cloud_system_popup_maintain();

  $endTime = microtime(TRUE);
  $period = $endTime - $beginTime;
  drushLog("结束导入po文件，用时：" . $period . '秒');
}

/**
 * 重新导入数据前清空表.
 */
function cloud_system_popup_maintain() {
  $path = drupal_get_path('module', 'cloud_system');
  $file = \Drupal::root() . '/' . $path . '/locale/locale.po';

  $sucess = $error = [];
  if ($path && file_exists($file)) {
    $po = new PoFileLoader();
    $loader = $po->load($file, '');
    $messages = $loader->all('messages');
    if (!empty($messages)) {
      $redis = \Drupal::service('cloud_system.redis')->getClient('redis_po');
      foreach ($messages as $key => $value) {
        if (empty($key)) {
          //drushLog("导入" . $key . "失败, key为空!", 'error');
          continue;
        }
        if (empty($value)) {
          $error[] = $key;
          drushLog("导入" . $key . "失败, value为空!", 'error');
          continue;
        }

        $md5_key = md5($key);
        $redis->set(md5($key), serialize([
          'en' => $key,
          'zh' => $value
        ]));

        $redis_key = $redis->get($md5_key);
        if (!empty($redis_key)) {
          $sucess[] = $redis_key;
          drushLog("导入" . $redis_key . "成功!");
        }
        else {
          $error[] = $redis_key;
          drushLog("导入" . $redis_key . "失败!", 'error');
        }
      }
    }
  }

  if (!empty($sucess)) {
    drushLog("成功导入" . count($sucess) . "条!");
  }

  if (!empty($error)) {
    drushLog("导入失败" . count($error) . "条!", 'error');
  }
}