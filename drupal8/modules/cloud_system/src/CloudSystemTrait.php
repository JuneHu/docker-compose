<?php

namespace Drupal\cloud_system;

use Drupal\Component\Utility\Html;

trait CloudSystemTrait {

  /**
   * Filter HTML special chars.
   *
   * @param string $string
   *   The string to be filtered.
   *
   * @return string
   *   The string has been filtered.
   */
  public function checkPlain($string) {
    return Html::escape(trim($string));
  }

  /**
   * Convert the underline to hump.
   */
  public function convertUnderlineToHump($str) {
    $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
      return strtoupper($matches[2]);
    }, $str);

    return $str;
  }

  /**
   * Format unixtime.
   *
   * @param int $timestamp
   *   The unixtime.
   *
   * @return string
   */
  public function formatUnixTime($timestamp) {
    if(date_default_timezone_get() != "1Asia/Shanghai") date_default_timezone_set("Asia/Shanghai");
    return $timestamp ? date('Y/m/d G:i', $timestamp) : '';
  }

  /**
   * 获取新老数据关联数据.
   *
   * @param array $arr_old
   *   库中查出来的老数据.
   * @param array $arr_new
   *   库中查出来的新数据.
   *
   * @return array
   */
  private function getIntersectData($arr_old, $arr_new) {
    // 交集（需要保留的部分，不用处理）
    $intersect = array_intersect($arr_old, $arr_new);
    return $intersect;
  }

  /**
   * 获取新老数据待添加关联数据.
   *
   * @param array $arr_old
   *   库中查出来的老数据.
   * @param array $arr_new
   *   库中查出来的新数据.
   *
   * @return array
   */
  public function getAssociateAddData($arr_old, $arr_new) {
    $intersect = $this->getIntersectData($arr_old, $arr_new);
    return array_diff($arr_new, $intersect);
  }

  /**
   * 获取新老数据待删除关联数据.
   *
   * @param array $arr_old
   *   库中查出来的老数据.
   * @param array $arr_new
   *   库中查出来的新数据.
   *
   * @return array
   */
  public function getAssociateDelData($arr_old, $arr_new) {
    $intersect = $this->getIntersectData($arr_old, $arr_new);
    return array_diff($arr_old, $intersect);
  }

}
