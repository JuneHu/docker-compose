<?php

namespace Drupal\cloud_system;

use Drupal\user\Entity\Role;

class CloudSystemUtils {

  /**
   * Whether is boss platform.
   */
  public static function isBoss() {
    $platform = \Drupal::config('cloud_system.settings')->get('platform');
    return $platform == 'is_boss';
  }

  /**
   * Whether is portal platform.
   */
  public static function isPortal() {
    $platform = \Drupal::config('cloud_system.settings')->get('platform');
    return $platform == 'is_portal';
  }

  /**
   * Whether is api platform.
   */
  public static function isApi() {
    $platform = \Drupal::config('cloud_system.settings')->get('platform');
    return $platform == 'is_api';
  }

  /**
   * Whether is dev platform.
   */
  public static function isDev() {
    $platform = \Drupal::config('cloud_system.settings')->get('platform');
    return $platform == 'is_dev';
  }

  /**
   * Translates a string to the current request language or to a given
   * language.
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $args
   *   (optional) An associative array of replacements to make after
   *   translation. Based on the first character of the key, the value is
   *   escaped and/or themed. See
   *   \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
   *   details.
   *
   * @return string
   */
  public static function t(string $string, array $args = []) {
    $locale = \Drupal::request()->headers->get('Accept-Language');
    $browserLocale = strtolower(str_split($locale, 2)[0]);
    $redis = \Drupal::service('cloud_system.redis')->getClient('redis_po');
    $values = $redis->get(md5($string));
    if (!empty($values)) {
      $value = unserialize($values);
      if (!empty($value) && isset($value[$browserLocale])) {
        $str = $value[$browserLocale];
      }
      else {
        $str = $value['en'];
      }

      if (empty($args)) {
        return $str;
      }
      else {
        // Transform arguments before inserting them.
        return vsprintf($str, $args);
      }
    }

    return "请翻译返回值ERROR MESSAGE!" . $string;
    //return $string;
  }

  /**
   * Retrieves cache data.
   *
   * @param string $cid
   *   The cache key.
   * @param array $data
   *   The cache data.
   * @param int $expire
   *   The cache expires.
   *
   * @return array | bool
   */
  public static function cache($cid, $data = [], $expire = 300) {
    if (!empty($data)) {
      // Set cache.
      $expire = time() + $expire;
      return \Drupal::cache()->set($cid, $data, $expire);
    }

    $return = FALSE;
    if ($cache = \Drupal::cache()->get($cid)) {
      $return = $cache->data;
    }
    return $return;
  }

  /**
   * curl get
   *
   * @param string $url
   * @param array $options
   *
   * @return mixed
   */
  public static function curlGet($url = '', $options = array()) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if (!empty($options)) {
      curl_setopt_array($ch, $options);
    }
    // https请求 不验证证书和host.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }

  /**
   * curl post
   *
   * @param string $url
   * @param string $postData
   * @param array $options
   *
   * @return mixed
   */
  public static function curlPost($url = '', $postData = '', $options = array()) {
    if (is_array($postData)) {
      $postData = http_build_query($postData);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if (!empty($options)) {
      curl_setopt_array($ch, $options);
    }
    // https请求 不验证证书和host.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }

  /**
   * 创建随机字符串.
   *
   * @param string $length
   *
   * @return mixed
   */
  public static function createNonceStr($length = 16) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  /**
   * 将数组转为xml.
   *
   * @param array $arr
   *
   * @return mixed
   */
  public static function arrayToXml($arr) {
    $xml = "<xml>";
    foreach ($arr as $key => $val) {
      if (is_numeric($val)) {
        $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
      }
      else {
        $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
      }
    }
    $xml .= "</xml>";
    return $xml;
  }

  /**
   * 计算sign标签.
   *
   * @param array $params
   * @param string $key
   *
   * @return mixed
   */
  public static function getSign($params, $key) {
    ksort($params, SORT_STRING);
    $unSignParaString = self::formatQueryParaMap($params, FALSE);
    $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
    return $signStr;
  }

  /**
   * 将参数数组拼接成字符串并转为大写.
   *
   * @param array $paraMap
   * @param Boolean $urlEncode
   *
   * @return mixed
   */
  public static function formatQueryParaMap($paraMap, $urlEncode = FALSE) {
    $buff = "";
    ksort($paraMap);
    foreach ($paraMap as $k => $v) {
      if (NULL != $v && "null" != $v) {
        if ($urlEncode) {
          $v = urlencode($v);
        }
        $buff .= $k . "=" . $v . "&";
      }
    }
    $reqPar = '';
    if (strlen($buff) > 0) {
      $reqPar = substr($buff, 0, strlen($buff) - 1);
    }
    return $reqPar;
  }

  /**
   * Diff文本内容.
   *
   * @param string $old_content
   * @param string $new_content
   *
   * @return string
   */
  public static function diffString($old_content, $new_content) {
    $diff_content = '';
    if (function_exists('xdiff_string_diff')) {
      $diff_content = xdiff_string_diff($old_content, $new_content);
      if (!empty($diff_content)) {
        $dfcontents = array();
        $diffs = array_filter(explode("\n", $diff_content));
        if (!empty($diffs)) {
          foreach ($diffs as $df) {
            $substr = substr($df, 0, 1);
            $str = $df;
            if ($substr == "+") {
              $str = "<b class='diff-new'>" . $df . "</b>";
            }
            else {
              if ($substr == '-') {
                $str = "<b class='diff-remove'>" . $df . "</b>";
              }
            }
            $dfcontents[] = $str;
          }
        }
        $diff_content = implode("\n", $dfcontents);
      }
    }
    return $diff_content;
  }

  /**
   * Sort for multi array.
   *
   * @usage:
   *   $arr1 = array(
   *      array('id'=>1,'name'=>'aA','cat'=>'cc'),
   *      array('id'=>2,'name'=>'aa','cat'=>'dd'),
   *      array('id'=>3,'name'=>'bb','cat'=>'cc'),
   *      array('id'=>4,'name'=>'bb','cat'=>'dd')
   * );
   *
   * $arr2 = arrayMultiSort($arr1, array('name'=>SORT_DESC, 'cat'=>SORT_ASC));
   *
   * @param $array
   * @param $cols
   *
   * @return array
   */
  public static function arrayMultiSort($array, $cols) {
    $col_arr = [];
    foreach ($cols as $col => $order) {
      $col_arr[$col] = [];
      foreach ($array as $k => $row) {
        $col_arr[$col]['_' . $k] = strtolower($row[$col]);
      }
    }
    $eval = 'array_multisort(';
    foreach ($cols as $col => $order) {
      $eval .= '$col_arr[\'' . $col . '\'],' . $order . ',';
    }
    $eval = substr($eval, 0, -1) . ');';
    eval($eval);
    $ret = [];
    foreach ($col_arr as $col => $arr) {
      foreach ($arr as $k => $v) {
        $k = substr($k, 1);
        if (!isset($ret[$k])) {
          $ret[$k] = $array[$k];
        }
        $ret[$k][$col] = $array[$k][$col];
      }
    }
    return $ret;
  }

  /**
   * 获取所有角色
   *
   * @param null
   * @return array
   *
   * [
   *   "research and development" => "研发",
   *   "Administrator" => "管理员"
   * ]
   */
  public static function getAllRole() {
    $result = Role::loadMultiple();
    $return = [];
    if (!empty($result)) {
      foreach ($result as $rid => $obj) {
        $return[$rid] = $obj->label();
      }
    }
    return $return;
  }

}
