<?php

namespace Drupal\cloud_system\IpTool;

/**
 * Class to determine if an IP is located in a specific range as
 * specified via several alternative formats.
 *
 * @Usage
 * // Validating
 * $status = IpTool::isValid('192.168.1.1'); // true
 * $status = IpTool::isValid('192.168.1.256'); // false
 *
 * // ip2long, long2ip
 * /// IPv4
 * $long = IpTool::ip2long('192.168.1.1'); // 3232235777
 * $dec = IpTool::long2ip('3232235777'); // 192.168.1.1
 *
 * /// IPv6
 * $long = IpTool::ip2long('fe80:0:0:0:202:b3ff:fe1e:8329'); //338288524927261089654163772891438416681
 * $dec = IpTool::long2ip('338288524927261089654163772891438416681', true); //fe80::202:b3ff:fe1e:8329
 *
 *
 * // Matching
 * /// IPv4
 * $status = IpTool::match('192.168.1.1', '192.168.1.*'); // true
 * $status = IpTool::match('192.168.1.1', '192.168.*.*'); // true
 * $status = IpTool::match('192.168.1.1', '192.168.*.*'); // true
 * $status = IpTool::match('192.168.1.1', '192.168.0.*'); // false
 * $status = IpTool::match('192.168.1.1', '192.168.1/24'); // true
 * $status = IpTool::match('192.168.1.1', '192.168.1.1/255.255.255.0'); // true
 * $status = IpTool::match('192.168.1.1', '192.168.0/24'); // false
 * $status = IpTool::match('192.168.1.1', '192.168.0.0/255.255.255.0'); // false
 * $status = IpTool::match('192.168.1.5', '192.168.1.1-192.168.1.10'); // true
 * $status = IpTool::match('192.168.5.5', '192.168.1.1-192.168.10.10'); // true
 * $status = IpTool::match('192.168.5.5', '192.168.6.1-192.168.6.10');
 * $status = IpTool::match('192.168.1.1', array('122.128.123.123', '192.168.1.*', '192.168.123.124')); // true
 * $status = IpTool::match('192.168.1.1', array('192.168.123.*', '192.168.123.124'));
 *
 * /// IPv6
 * $status = IpTool::match('2001:cdba:0000:0000:0000:0000:3257:9652', '2001:cdba:0000:0000:0000:0000:3257:*'); // true
 * $status = IpTool::match('2001:cdba:0000:0000:0000:0000:3257:9652', '2001:cdba:0000:0000:0000:0000:*:*'); // true
 * $status = IpTool::match('2001:cdba:0000:0000:0000:0000:3257:9652', '2001:cdba:0000:0000:0000:0000:3257:1234-2001:cdba:0000:0000:0000:0000:3257:9999'); //true
 *
 * $status = IpTool::match('2001:cdba:0000:0000:0000:0000:3258:9652', '2001:cdba:0000:0000:0000:0000:3257:*'); // false
 * $status = IpTool::match('2001:cdba:0000:0000:0000:1234:3258:9652', '2001:cdba:0000:0000:0000:0000:*:*'); // false
 * $status = IpTool::match('2001:cdba:0000:0000:0000:0000:3257:7778', '2001:cdba:0000:0000:0000:0000:3257:1234-2001:cdba:0000:0000:0000:0000:3257:7777'); //false
 */
class IpTool {
  protected static $ip;

  protected static $isv6 = FALSE;


  /**
   * Checks if an IP is valid.
   *
   * @param string $ip
   *   The ip.
   * @param boolean $include_private
   *   是否包含私有网络，默认包含.
   *   10.0.0.0/8, 172.16.0.0/12 and 192.168.0.0/16.
   *   the IPv6 addresses starting with FD or FC.
   * @param boolean $include_reserve
   *   是否包含保留地址，默认包含.
   *   0.0.0.0/8, 169.254.0.0/16, 127.0.0.0/8 and 240.0.0.0/4.
   *   ::1/128, ::/128, ::ffff:0:0/96 and fe80::/10.
   *
   * @return boolean true if IP is valid, otherwise false.
   */
  public static function isValid($ip, $include_private = TRUE, $include_reserve = TRUE) {
    $valid = self::isIpV4($ip, $include_private, $include_reserve);
    if ($valid) {
      self::$ip = $ip;
      self::$isv6 = FALSE;
      return TRUE;
    }

    $valid = self::isIpV6($ip, $include_private, $include_reserve);
    if ($valid) {
      self::$ip = $ip;
      self::$isv6 = TRUE;
      return TRUE;
    }
    return FALSE;
  }


  /**
   * Checks if an IP is valid IPv4 format.
   *
   * @param string $ip
   *   The ip.
   * @param boolean $include_private
   *   是否包含私有网络，默认包含.
   *   10.0.0.0/8, 172.16.0.0/12 and 192.168.0.0/16.
   *   the IPv6 addresses starting with FD or FC.
   * @param boolean $include_reserve
   *   是否包含保留地址，默认包含.
   *   0.0.0.0/8, 169.254.0.0/16, 127.0.0.0/8 and 240.0.0.0/4.
   *   ::1/128, ::/128, ::ffff:0:0/96 and fe80::/10.
   *
   * @return boolean true if IP is valid IPv4, otherwise false.
   */
  public static function isIpV4($ip, $include_private = TRUE, $include_reserve = TRUE) {
    if (!$include_private && !$include_reserve) {
      $filter_flag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
    }
    elseif (!$include_private) {
      $filter_flag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE;
    }
    elseif (!$include_reserve) {
      $filter_flag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE;
    }
    else {
      $filter_flag = FILTER_FLAG_IPV4;
    }

    return filter_var($ip, FILTER_VALIDATE_IP, $filter_flag);
  }

  /**
   * 校验IP是否是IPv6的地址
   *
   * @param string $ip
   *   The ip.
   * @param boolean $include_private
   *   是否包含私有网络，默认包含.
   *   10.0.0.0/8, 172.16.0.0/12 and 192.168.0.0/16.
   *   the IPv6 addresses starting with FD or FC.
   * @param boolean $include_reserve
   *   是否包含保留地址，默认包含.
   *   0.0.0.0/8, 169.254.0.0/16, 127.0.0.0/8 and 240.0.0.0/4.
   *   ::1/128, ::/128, ::ffff:0:0/96 and fe80::/10.
   *
   * @return boolean
   */
  public static function isIpV6($ip, $include_private = TRUE, $include_reserve = TRUE) {
    if (!$include_private && !$include_reserve) {
      $filter_flag = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
    }
    elseif (!$include_private) {
      $filter_flag = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE;
    }
    elseif (!$include_reserve) {
      $filter_flag = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE;
    }
    else {
      $filter_flag = FILTER_FLAG_IPV6;
    }

    return filter_var($ip, FILTER_VALIDATE_IP, $filter_flag);
  }

  /**
   * Checks if an IP is local
   *
   * @param  string $ip IP
   *
   * @return boolean     true if the IP is local, otherwise false
   */
  public static function isLocal($ip) {
    $localIpv4Ranges = [
      '10.*.*.*', // RFC 1918 (private)
      '127.*.*.*', // loopback
      '192.168.*.*', // RFC 1918 (private)
      '169.254.*.*', // link-local
      '172.16.0.0-172.31.255.255', // RFC 1918 (private)
      '224.*.*.*',
      '0.0.0.0', // this network
    ];

    $localIpv6Ranges = [
      'fe80::/10', // link-local
      '0:0:0:0:0:0:0:1', // loopback
      '::1/128', // loopback
      'fc00::/7', // RFC 4193 (local)
    ];

    if (self::isIpV4($ip)) {
      return self::match($ip, $localIpv4Ranges);
    }

    if (self::isIpV6($ip)) {
      return self::match($ip, $localIpv6Ranges);
    }

    return FALSE;
  }

  /**
   * Checks if an IP is remote
   *
   * @param  string $ip IP
   *
   * @return boolean
   *   True if the IP is remote, otherwise false.
   */
  public static function isRemote($ip) {
    return !self::isLocal($ip);
  }

  /**
   * Checks if an IP is part of an IP range.
   *
   * @param string $ip IPv4/IPv6
   * @param mixed $ranges IP range specified in one of the following formats:
   * Wildcard format:     1.2.3.*
   * CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
   * Start-End IP format: 1.2.3.0-1.2.3.255
   *
   * @return boolean
   *   True if IP is part of range, otherwise false.
   */
  public static function match($ip, $ranges) {
    if (is_array($ranges)) {
      foreach ($ranges as $range) {
        $match = self::compare($ip, $range);
        if ($match) {
          return TRUE;
        }
      }
    }
    else {
      return self::compare($ip, $ranges);
    }
    return FALSE;
  }

  /**
   * Checks if an IP is part of an IP range.
   *
   * @param string $ip IPv4/IPv6
   * @param string $range IP range specified in one of the following formats:
   * Wildcard format:     1.2.3.* OR 2001:cdba:0000:0000:0000:0000:3257:*
   * CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
   * Start-End IP format: 1.2.3.0-1.2.3.255 OR
   *   2001:cdba:0000:0000:0000:0000:3257:0001-2001:cdba:0000:0000:0000:0000:3257:1000
   *
   * @return boolean
   *   True if IP is part of range, otherwise false.
   */
  public static function compare($ip, $range) {
    if (!self::isValid($ip)) {
      throw new \InvalidArgumentException('Input IP "' . $ip . '" is invalid!');
    }

    $status = FALSE;
    if (strpos($range, '/') !== FALSE) {
      $status = self::processWithSlash($range);
    }
    else {
      if (strpos($range, '*') !== FALSE) {
        $status = self::processWithAsterisk($range);
      }
      else {
        if (strpos($range, '-') !== FALSE) {
          $status = self::processWithMinus($range);
        }
        else {
          $status = ($ip === $range);
        }
      }
    }
    return $status;
  }


  /**
   * Checks if an IP is part of an IP range.
   *
   * @param string $range
   *   IP range specified in one of the following formats:
   *   CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
   *
   * @return bool
   *   True if IP is part of range, otherwise false.
   */
  protected static function processWithSlash($range) {
    list($range, $netmask) = explode('/', $range, 2);

    if (self::$isv6) {
      if (strpos($netmask, ':') !== FALSE) {
        $netmask = str_replace('*', '0', $netmask);
        $netmask_dec = self::ip2long($netmask);
        return ((self::ip2long(self::$ip) & $netmask_dec) == (self::ip2long($range) & $netmask_dec));
      }
      else {
        $x = explode(':', $range);
        while (count($x) < 8) {
          $x[] = '0';
        }

        list($a, $b, $c, $d, $e, $f, $g, $h) = $x;
        $range = sprintf(
          "%u:%u:%u:%u:%u:%u:%u:%u",
          empty($a) ? '0' : $a,
          empty($b) ? '0' : $b,
          empty($c) ? '0' : $c,
          empty($d) ? '0' : $d,
          empty($e) ? '0' : $e,
          empty($f) ? '0' : $f,
          empty($g) ? '0' : $g,
          empty($h) ? '0' : $h
        );
        $range_dec = self::ip2long($range);
        $ip_dec = self::ip2long(self::$ip);
        $wildcard_dec = pow(2, (32 - $netmask)) - 1;
        $netmask_dec = ~$wildcard_dec;

        return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
      }
    }
    else {
      if (strpos($netmask, '.') !== FALSE) {
        $netmask = str_replace('*', '0', $netmask);
        $netmask_dec = self::ip2long($netmask);
        return ((self::ip2long(self::$ip) & $netmask_dec) == (self::ip2long($range) & $netmask_dec));
      }
      else {
        $x = explode('.', $range);
        while (count($x) < 4) {
          $x[] = '0';
        }

        list($a, $b, $c, $d) = $x;
        $range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
        $range_dec = self::ip2long($range);
        $ip_dec = self::ip2long(self::$ip);
        $wildcard_dec = pow(2, (32 - $netmask)) - 1;
        $netmask_dec = ~$wildcard_dec;

        return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
      }
    }

    return FALSE;
  }


  /**
   * Checks if an IP is part of an IP range.
   *
   * @param string $range
   *   IP range specified in one of the following formats:
   *   Wildcard format: 1.2.3.* OR 2001:cdba:0000:0000:0000:0000:3257:*
   *
   * @return boolean
   *   True if IP is part of range, otherwise false.
   */
  protected static function processWithAsterisk($range) {
    if (strpos($range, '*') !== FALSE) {
      $lowerRange = self::$isv6 ? '0000' : '0';
      $upperRange = self::$isv6 ? 'ffff' : '255';

      $lower = str_replace('*', $lowerRange, $range);
      $upper = str_replace('*', $upperRange, $range);

      $range = $lower . '-' . $upper;
    }

    if (strpos($range, '-') !== FALSE) {
      return self::processWithMinus($range);
    }

    return FALSE;
  }

  /**
   * Checks if an IP is part of an IP range.
   *
   * @param string $range
   *   IP range specified in one of the following formats:
   *    Start-End IP format: 1.2.3.0-1.2.3.255 OR
   *    2001:cdba:0000:0000:0000:0000:3257:0001-2001:cdba:0000:0000:0000:0000:3257:1000
   *
   * @return boolean
   *   True if IP is part of range, otherwise false.
   */
  protected static function processWithMinus($range) {
    list($lower, $upper) = explode('-', $range, 2);
    $lower_dec = self::ip2long($lower);
    $upper_dec = self::ip2long($upper);
    $ip_dec = self::ip2long(self::$ip);

    return (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec));
  }


  /**
   * Gets IP long representation
   *
   * @param string $ip
   *   IPv4 or IPv6
   *
   * @return integer
   *   If IP is valid returns IP long representation, otherwise -1.
   */
  public static function ip2long($ip) {
    $long = -1;
    if (self::isIpV6($ip)) {
      if (!function_exists('bcadd')) {
        throw new \RuntimeException('BCMATH extension not installed!');
      }

      $ip_n = inet_pton($ip);
      $bin = '';
      for ($bit = strlen($ip_n) - 1; $bit >= 0; $bit--) {
        $bin = sprintf('%08b', ord($ip_n[$bit])) . $bin;
      }

      $dec = '0';
      for ($i = 0; $i < strlen($bin); $i++) {
        $dec = bcmul($dec, '2', 0);
        $dec = bcadd($dec, $bin[$i], 0);
      }
      $long = $dec;
    }
    else {
      if (self::isIpV4($ip)) {
        $long = ip2long($ip);
      }
    }
    return $long;
  }


  /**
   * Gets IP string representation from IP long
   *
   * @param integer $dec
   *   IPv4 or IPv6 long
   * @param bool $ipv6
   *
   * @return string
   *  If IP is valid returns IP string representation, otherwise ''.
   */
  public static function long2ip($dec, $ipv6 = FALSE) {
    $ipstr = '';
    if ($ipv6) {
      if (!function_exists('bcadd')) {
        throw new \RuntimeException('BCMATH extension not installed!');
      }

      $bin = '';
      do {
        $bin = bcmod($dec, '2') . $bin;
        $dec = bcdiv($dec, '2', 0);
      } while (bccomp($dec, '0'));

      $bin = str_pad($bin, 128, '0', STR_PAD_LEFT);
      $ip = array();
      for ($bit = 0; $bit <= 7; $bit++) {
        $bin_part = substr($bin, $bit * 16, 16);
        $ip[] = dechex(bindec($bin_part));
      }
      $ip = implode(':', $ip);
      $ipstr = inet_ntop(inet_pton($ip));
    }
    else {
      $ipstr = long2ip($dec);
    }
    return $ipstr;
  }

  /**
   * Match the ip range.
   *
   * @param $ip
   * @param $range
   *
   * @return bool
   */
  public static function matchRange($ip, $range) {
    $ipParts = explode('.', $ip);
    $rangeParts = explode('.', $range);

    $ipParts = array_filter($ipParts);
    $rangeParts = array_filter($rangeParts);

    $ipParts = array_slice($ipParts, 0, count($rangeParts));

    return implode('.', $rangeParts) === implode('.', $ipParts);
  }

  /**
   * Add numbers.
   */
  public static function add($ip, $num) {
    if (function_exists('gmp_strval')) {
      return gmp_strval(gmp_add($ip, $num));
    }
    else {
      throw new \RuntimeException('GMP extension not installed!');
    }
  }

  /**
   * Calculates diff between two IP addresses
   *
   * @param string $ip1
   * @param string $ip2
   *
   * @return string
   */
  public static function diff($ip1, $ip2) {
    if (function_exists('gmp_strval')) {
      return gmp_strval(gmp_sub($ip2, $ip1));
    }
    else {
      throw new \RuntimeException('GMP extension not installed!');
    }
  }


}