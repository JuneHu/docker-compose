<?php

namespace Drupal\cloud_system;

use Drupal\Core\Database\Connection;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\Entity\User;
use GuzzleHttp\Psr7;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a base implementation for all custom plugin.
 *
 * This class is uninstantiatable and un-extendable. It acts to encapsulate
 * all control and shepherding of database connections into a single location
 * without the use of globals.
 */
class CloudSystemBase implements CloudSystemBaseInterface {

  /**
   * Information about the current HTTP request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request_stack;

  /**
   * Information about the current HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  public $request;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  public $httpClient;

  /**
   * Database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  public $database;

  /**
   * The http concurrency limits.
   */
  private $concurrency;

  /**
   * The http concurrency response data.
   */
  private $concurrencyResponseData = [];

  /**
   * Crypt string, 不能出现重复字符，内有A-Z,a-z,0-9,/,=,+,_,.
   *
   * @var string
   */
  const CRYPT_STRING = "st=lDEFABCNOPyzghi_jQRST-UwxkVWXYZabcdefIJK6/7nopqr89LMmGH012345uv";

  /**
   * API URL.
   *
   * @var string
   */
  const API_URL = 'https://api3.verycloud.cn/API/';

  /**
   * Token expire time.
   *
   * @var int
   */
  const TOKEN_EXPIRE = 600;

  /**
   * Cache expire time.
   *
   * @var int
   */
  const CACHE_EXPIRE = 300;

  /**
   * Personal account.
   *
   * @var int
   */
  const ACCOUNT_TYPE_PERSONAL = 1;

  /**
   * Company account.
   *
   * @var int
   */
  const ACCOUNT_TYPE_COMPANY = 2;

  /**
   * Boss account.
   *
   * @var int
   */
  const ACCOUNT_TYPE_BOSS = 3;

  /**
   * Agent account.
   *
   * @var int
   */
  const ACCOUNT_TYPE_AGENT = 4;

  /**
   * Token secret.
   *
   * @var string
   */
  const TOKEN_SECRET = '*&amp;$^^$##ffbbkksssoo(*&amp;)';

  /**
   * 日志新增操作.
   *
   * @var int
   */
  const CLOUD_LOG_OP_INSERT = 1;

  /**
   * 日志删除操作.
   *
   * @var int
   */
  const CLOUD_LOG_OP_DELETE = 2;

  /**
   * 日志更新操作.
   *
   * @var int
   */
  const CLOUD_LOG_OP_UPDATE = 3;

  /**
   * Constructs some object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The RequestStack object.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\Database\Connection $database
   *   The database object.
   */
  public function __construct(RequestStack $request_stack = NULL, ClientInterface $httpClient = NULL, Connection $database = NULL) {
    $this->requestStack = $request_stack;
    $this->request = $this->requestStack->getCurrentRequest();
    $this->httpClient = $httpClient;
    $this->database = $database;
  }

  /**
   * Provides the set of string encryption.
   *
   * @param string $cryptContent
   *   Encrypt string.
   *
   * @return string
   *   The encrypt string.
   */
  public function encrypt($cryptContent = '') {
    if (empty($cryptContent)) {
      return NULL;
    }

    // 密锁串，不能出现重复字符，内有A-Z,a-z,0-9,/,=,+,_,
    // 随机找一个数字，并从密锁串中找到一个密锁值.
    $cryptString = static::CRYPT_STRING;
    $cryptSalt = \Drupal::config('cloud_system.settings')->get('crypt_salt');
    $lockLen = strlen($cryptString);
    $lockCount = rand(0, $lockLen - 1);
    $randomLock = $cryptString[$lockCount];
    // 结合随机密锁值生成MD5后的密码.
    $password = md5($cryptSalt . $randomLock);
    // 开始对字符串加密.
    $cryptContent = base64_encode($cryptContent);
    $tmpStream = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($cryptContent); $i++) {
      $k = ($k == strlen($password)) ? 0 : $k;
      $j = (strpos($cryptString, $cryptContent[$i]) + $lockCount + ord($password[$k])) % ($lockLen);
      $tmpStream .= $cryptString[$j];
      $k++;
    }
    return $tmpStream . $randomLock;
  }

  /**
   * Provides the set of string decryption.
   *
   * @param string $decryptContent
   *   Decrypt string.
   *
   * @return string
   *   The decrypt string.
   */
  public function decrypt($decryptContent = '') {
    if (empty($decryptContent)) {
      return NULL;
    }

    $cryptString = static::CRYPT_STRING;
    $cryptSalt = \Drupal::config('cloud_system.settings')->get('crypt_salt');
    $lockLen = strlen($cryptString);
    // 获得字符串长度.
    $txtLen = strlen($decryptContent);
    // 截取随机密锁值.
    $randomLock = $decryptContent[$txtLen - 1];
    // 获得随机密码值的位置.
    $lockCount = strpos($cryptString, $randomLock);
    // 结合随机密锁值生成MD5后的密码.
    $password = md5($cryptSalt . $randomLock);
    // 开始对字符串解密.
    $decryptContent = substr($decryptContent, 0, $txtLen - 1);
    $tmpStream = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($decryptContent); $i++) {
      $k = ($k == strlen($password)) ? 0 : $k;
      $j = strpos($cryptString, $decryptContent[$i]) - $lockCount - ord($password[$k]);
      while ($j < 0) {
        $j = $j + ($lockLen);
      }
      $tmpStream .= $cryptString[$j];
      $k++;
    }
    return base64_decode($tmpStream);
  }

  /**
   * Get the real client ip address.
   *
   * @return string
   *   Ip value.
   */
  public function realIp() {
    $inner_ip = join('|', $this->getInnerIp());
    $real_ip = $this->requestStack->getCurrentRequest()->headers->get('X-REAL-IP');

    if ($real_ip && !preg_match('/^(' . $inner_ip . ').+/', $real_ip)) {
      return $real_ip;
    }

    // Default client Ip.
    $ip = $this->requestStack->getCurrentRequest()->getClientIp();

    // Compatible to verycloud CDN.
    $cdn_ips = $this->requestStack->getCurrentRequest()->server->get('HTTP_X_FORWARDED_FOR');

    if (isset($cdn_ips) && !empty($cdn_ips)) {
      $ips = explode(', ', $cdn_ips);

      if ($ip) {
        array_unshift($ips, $ip);
      }

      foreach ($ips as $item) {
        if (!preg_match('/^(' . $inner_ip . ').+/', $item)) {
          $ip = $item;
          break;
        }
      }
    }

    return $ip;
  }

  /**
   * 内网IP地址段.
   */
  public function getInnerIp() {
    $ips = [
      '10.',
      '192.168.',
      '127.',
      '169.254.',
    ];

    for ($i = 16; $i <= 31; $i++) {
      $ips[] = '172.' . $i . '.';
    }

    return $ips;
  }

  /**
   * Performs an HTTP request.
   *
   * This is a flexible and powerful HTTP client implementation. Correctly
   * handles GET, POST, PUT or any other HTTP requests. Handles redirects.
   *
   * @param string $uri
   *   A string containing a fully qualified URI.
   * @param array $options
   *   (optional) An array that can have one or more of the following elements:
   *   - headers: An array containing request headers to send as
   *     name/value pairs.
   *   - method: A string containing the request method. Defaults to 'GET'.
   *   - data: A string containing the request body, formatted as
   *     'param=value&param=value&...'. Defaults to NULL.
   *   - max_redirects: An integer representing how many times a redirect
   *     may be followed. Defaults to 3.
   *   - timeout: A float representing the maximum number of seconds the
   *     function call may take. The default is 30 seconds. If a timeout
   *     occurs, the error code is set to the HTTP_REQUEST_TIMEOUT constant.
   *   - context: A context resource created with stream_context_create().
   *
   * @return string
   *   String if the request was successfully accepted, otherwise FALSE..
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function httpRequest($uri, array $options = []) {
    $nowTime = time();
    $method = isset($options['method']) ? $options['method'] : 'POST';
    $accept = isset($options['accept']) ? $options['accept'] : 'application/json';
    $content_type = isset($options['content-type']) ? $options['content-type'] : 'application/json; charset=utf-8';
    $token = isset($options['token']) ? $options['token'] : $this->encrypt(json_encode(['expire' => $nowTime + 300]));
    $body = isset($options['body']) ? $options['body'] : [];
    $needError = isset($options['needError']) ? $options['needError'] : '';
    $gmdate = gmdate('D, d M Y H:i:s T', $nowTime);

    $headers = [
      'verify' => FALSE,
      'http_errors' => FALSE,
    ];

    if (isset($options['headers']) && !empty($options['headers'])) {
      $headers['headers'] = $options['headers'];
    }
    else {
      $headers = [
        'User-Agent' => 'API/2.0 JUST DO IT',
        'Date' => $gmdate,
        'Accept-Encoding' => 'gzip',
        'Accept-Language' => \Drupal::request()->headers->get('Accept-Language'),
        'Accept' => $accept,
        'Content-Type' => $content_type,
        'Authorization' => 'Bearer ' . $token,
        'X-REAL-IP' => $this->realIp(),
      ];
      $headers['headers'] = $headers;
    }

    $platform = \Drupal::config('cloud_system.settings')->get('platform');
    if ($platform && !is_string($body)) {
      $body[$platform] = TRUE;
    }

    if ($platform == 'is_portal') {
      $current_uid = $this->getCurrentUid();
      $body['uid'] = $current_uid;
    }
    else {
      $user_id = $this->getCurrentUid();
      if ($user_id && !is_string($body)) {
        $body['operatorId'] = $user_id;
      }
    }

    $curl_body = $curl_uri = '';
    if ($method == 'GET') {
      $headers['query'] = $body;
      $curl_uri = '?' . http_build_query($body);
    }
    elseif (FALSE !== strpos($content_type, 'application/json')) {
      $headers['body'] = $curl_body = json_encode($body);
    }
    elseif (FALSE !== strpos($content_type, 'application/x-www-form-urlencoded')) {
      $headers['form_params'] = $body;
      $curl_body = http_build_query($body);
    }
    else {
      // 其他格式，要把body数据置为空.
      $headers['body'] = $curl_body = $options['body'];
    }

    $start = microtime(TRUE);
    $mem = memory_get_usage(TRUE);
    $content = '';
    $code = 0;
    try {
      $response = $this->httpClient->request($method, $uri, $headers);
      $code = $response->getStatusCode();
      $content = $response->getBody()->getContents();

      $dblogEnable = \Drupal::config('cloud_system.settings')
        ->get('dblog_enable');
      if ($dblogEnable) {
        $curl_uri = $uri . $curl_uri;
        $curl_string = "<br/><br/> curl -X $method $curl_uri -H'Date:$gmdate' -H'Accept:$accept' -H'Content-Type:$content_type' -H'Authorization:Bearer $token'";
        $log_str = $code . $curl_string;
        \Drupal::logger($uri)
          ->info($log_str . " @message <br/><br/> response@response", [
            '@message' => $curl_body ? "-d'" . $curl_body . "'" : '',
            '@response' => $content,
          ]);
      }
    } catch (TransferException $e) {
      if ($e->hasResponse()) {
        $response = $e->getResponse();
        if ($response) {
          $code = $response->getStatusCode();
          $content = $response->getBody()->getContents();

          if ($code != 404) {
            $errorBody = 'API: ' . $uri
              . PHP_EOL . "请求机器：" . \Drupal::request()->getHost()
              . PHP_EOL . 'HTTP CODE:' . $code
              . PHP_EOL . '<b>Request:</b> '
              . PHP_EOL . Psr7\str($e->getRequest())
              . PHP_EOL . PHP_EOL . PHP_EOL . '<b>Response: </b>'
              . PHP_EOL . Psr7\str($response);

            $mail = \Drupal::config('cloud_system.email_config')
              ->get('default_mail');
            $this->sendMail($mail, [
              'subject' => 'API Request Error',
              'body' => $errorBody,
              // 'userName' => 'userName',.
            ]);
          }
        }
      }
    }
    $elapsed = (microtime(TRUE) - $start) * 1000;
    $used_mem = memory_get_usage(TRUE) - $mem;

    // Collect api status.
    /*$apiAnalyze = \Drupal::service('cloud_system.api.analyze');
    $apiAnalyze->collectApiStatus([
      'u' => $uri,
      'c' => $code,
      'p' => $body,
      'i' => $this->realIp(),
      'e' => $elapsed,
      'm' => $used_mem,
      't' => time(),
    ]);*/

    if ($code != 200) {
      if ($needError) {
        if ($content) {
          $content_arr = json_decode($content, TRUE);
          if ($content_arr && isset($content_arr['error'])) {
            $content = $content_arr['error'];
          }
          else {
            if (isset($content_arr['message'])) {
              $content = $content_arr['message'];
            }
            else {
              if (isset($content_arr['detail'])) {
                $content = $content_arr['detail'];
              }
            }
          }
        }
        throw new HttpException($code, $content ? $content : 'Bad Request.');
      }
      return FALSE;
    }

    return $content;
  }

  /**
   * Send the e-mail message.
   *
   * See
   * http://api.drupal.org/api/drupal/includes--mail.inc/interface/MailSystemInterface/7.
   *
   * @param string $mail
   *   Mailing list, separated by commas.
   * @param array $params
   *   A mail param array.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   */
  public function sendMail($mail, $params) {
    $account = \Drupal::currentUser();
    $params['body'] = [nl2br($params['body'])];

    $mail_config = \Drupal::configFactory()->getEditable('system.mail');
    $mail_config->set('interface.default', 'CloudMail')->save();

    $result = \Drupal::service('plugin.manager.mail')
      ->mail('cloud_system', 'send_mail', $mail, $account->getPreferredLangcode(), $params);

    if ($result['result'] !== TRUE) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * 捕获运行时的错误，并发送邮件，
   */
  public function sendThrowableExceptionMail($message) {
    $errorBody = 'API error: ' . PHP_EOL . \Drupal::request()->getRequestUri()
      . PHP_EOL . '<b>Request:</b> '
      . PHP_EOL . \Drupal::request()->__toString()
      . PHP_EOL . PHP_EOL . PHP_EOL . '<b>Error Info: </b>'
      . PHP_EOL . $message;
    $mail = \Drupal::config('cloud_system.email_config')->get('default_mail');
    return $this->sendMail($mail, [
      'subject' => 'API Error',
      'body'    => $errorBody,
    ]);
  }

  /**
   * Get access token.
   *
   * @param array $data
   *   Data.
   *
   * @return string|bool
   *   Token or FALSE.
   */
  public function getAccessToken($data = []) {
    $now_time = time();
    $currentUser = \Drupal::currentUser();
    if (!$currentUser || $currentUser->isAnonymous()) {
      $session = \Drupal::service('session');

      if ($session->get('userID')) {
        $uid = (int) $session->get('userID');
      }
      else {
        $token = isset($data['token']) ? $data['token'] : '';
        if (empty($token)) {
          if ($this->request) {
            $bearer = $this->request->headers->get('Authorization');
            $token = array_pop(explode(' ', $bearer));
            if (empty($token)) {
              $token = $this->request->get('token');
            }
          }
        }
        if (empty($token)) {
          return FALSE;
        }
        $tokens = json_decode($this->decrypt($token), TRUE);

        if (empty($tokens) || !is_array($tokens)) {
          return FALSE;
        }

        $expire = $tokens['expire'] ? $now_time - $tokens['expire'] : 0;
        if ($expire > 0) {
          return FALSE;
        }

        $uid = $tokens['userID'] ?? 0;
      }

      if (!$uid) {
        return FALSE;
      }

      $user = user_load($uid);
      if (!$user) {
        return FALSE;
      }

      $username = $user->getAccountName();
      $password = $user->getPassword();
    }
    else {
      $username = $currentUser->getAccountName() ?? '';
      $password = \Drupal::service('session')->get('password') ?? '';
    }

    if (!$username || !$password) {
      return FALSE;
    }

    $cacheKey = 'access_token_' . $username;

    $cache = \Drupal::cache();
    $cache_resources = $cache->get($cacheKey);

    if (!$cache_resources) {
      $output = $this->getCacheAccessToken($cacheKey, $username, $password);
    }
    else {
      $return = $cache_resources->data;
      // 判断缓存中的access_token是否失效.
      if ($return['expires'] > $now_time) {
        $output = $return['access_token'];
      }
      else {
        $output = $this->getCacheAccessToken($cacheKey, $username, $password);
      }
    }

    return $output;
  }

  /**
   * Get access token.
   *
   * @param string $cacheKey
   *   Cache key.
   * @param string $username
   *   Username.
   * @param string $password
   *   Password.
   *
   * @return bool|string
   *   Token or FALSE
   */
  public function getCacheAccessToken($cacheKey, $username, $password) {
    $now_time = time();

    $send_data = [
      'username' => $username,
      'password' => $password,
    ];

    $options = [
      'method' => 'POST',
      'body' => $send_data,
    ];

    $output = FALSE;
    $return = $this->httpRequest(static::API_URL . 'OAuth/authorize', $options);
    if ($return) {
      $return = json_decode($return, TRUE);
      if (isset($return['code']) && $return['code'] == 1) {
        $output = $return['access_token'];
        $expires = $return['expires'];
        if ($now_time < $expires) {
          $cache = \Drupal::cache();
          $cache->set($cacheKey, $return, time() + self::CACHE_EXPIRE);
        }
      }
    }

    return $output;
  }

  /**
   * 获取访问接口端的token.
   *
   * @param $needUid
   *   Token中是否需要携带uid.
   *
   * @return bool|string
   *   FALSE or token.
   */
  public function getToken($needUid) {
    $tokenArr = [
      'expire' => time() + 600,
    ];

    if ($needUid) {
      $user = $this->getCurrentUser();
      $userID = isset($user['uid']) ? $user['uid'] : 0;
      if (!$userID) {
        $userID = \Drupal::service('session')->get('userID', 0);
        if (!$userID) {
          return FALSE;
        }
      }

      $tokenArr['userID'] = $userID;
    }

    return $this->encrypt(json_encode($tokenArr));
  }

  /**
   * Get API url.
   *
   * @param string $type
   *   地址类型.
   *
   * @return string
   *   API URL.
   */
  public function getApiUrl($type = 'user') {
    $config = \Drupal::config('cloud_system.settings');
    return $config->get($type . '_api') ? $config->get($type . '_api') : '';
  }

  /**
   * Validate username.
   *
   * @param string $username
   *   Username.
   *
   * @return bool
   *   Result.
   */
  public function validateUserName($username) {
    $error_message = user_validate_name($username);
    if (!empty($error_message)) {
      return $error_message;
    }

    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('Username', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($username);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }

  /**
   * Validate password.
   *
   * @param string $password
   *   Password.
   *
   * @return bool
   *   Result.
   */
  public function validatePassWord($password) {
    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('Password', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($password);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }

  /**
   * Validate email address.
   *
   * @param string $mail
   *   Email.
   *
   * @return bool
   *   Result.
   */
  public function validateEmail($mail) {
    // $isValid = \Drupal::service('email.validator')->isValid($mail);
    $isValid = filter_var($mail, FILTER_VALIDATE_EMAIL);
    if (!$isValid) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validate phone.
   *
   * @param string $phone
   *   Phone.
   *
   * @return bool
   *   Result.
   */
  public function validateCellPhone($phone) {
    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('Phone', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($phone);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }

  /**
   * Validate tel.
   *
   * @param string $tel
   *   The tel number.
   *
   * @return bool
   *   True if validated, otherwise string.
   */
  public function validateTel($tel) {
    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('Tel', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($tel);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }

  /**
   * Validates a domain name according to the rules at:
   * http://en.wikipedia.org/wiki/Hostname#Restrictions_on_valid_host_names.
   *
   * dot '.' delimited segments
   * Each segment must be between 1 and 63 characters
   * The entire thing must be <= 255 characters
   * Valid chars are 'a' to 'z', 'A' to 'Z', '0' to '9', and hyphen '-'
   * labels must not start or end with hyphen.
   *
   * Additionally, there must be at least 2 segments
   * Additionally, the last segment must be one of supported top level domains
   * (TLD) Additionally, must not start or end with dot '.'.
   *
   * @return bool
   *   Success if valid.
   */
  public function validateDomain($value) {
    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('Domain', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($value);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }

  /**
   * Validate QQ number.
   *
   * @param int $qq
   *   The QQ number.
   *
   * @return bool
   *   True if validated, otherwise string.
   */
  public function validateQQ($qq) {
    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('QQ', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($qq);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }

  /**
   * Validate MAC address.
   *
   * @param string $mac
   *   The MAC address.
   *
   * @return bool
   *   True if validated, otherwise string.
   */
  public function validateMac($mac) {
    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('MacAddr', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($mac);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }

  /**
   * Validate ip address.
   *
   * @param $ip
   *
   * @return bool
   */
  public function validateIp($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      return TRUE;
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      return TRUE;
    }
    return TRUE;
  }

  /**
   * Validate ID card.
   *
   * @param string $idcard
   *   The ID card.
   *
   * @return bool
   *   True if validated, otherwise string.
   */
  public function validateIdCard($idcard) {
    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('IdCard', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($idcard);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }

  /**
   * Validate ZipCode.
   *
   * @param int $zipCode
   *   The zipCode.
   *
   * @return bool
   *   True if validated, otherwise string.
   */
  public function validateZipCode($zipCode) {
    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('ZipCode', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($zipCode);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }

  /**
   * Validate BankNo.
   *
   * @param int $bankNo
   *   The bankNo.
   *
   * @return bool
   *   True if validated, otherwise string.
   */
  public function validateBankNo($bankNo) {
    $definition = BaseFieldDefinition::create('string')
      ->addConstraint('BankNo', []);
    $data = \Drupal::typedDataManager()->create($definition);
    $data->setValue($bankNo);
    $violations = $data->validate();
    if (count($violations) > 0) {
      return strip_tags($violations[0]->getMessage()->render());
    }

    return TRUE;
  }


  /**
   * Generate a token.
   *
   * @return string
   *   Token.
   */
  public function encodeToken() {
    $str = mt_rand(1000, 9999);
    $str2 = dechex($_SERVER['REQUEST_TIME'] - $str);
    return $str . substr(md5($str . self::TOKEN_SECRET), 0, 10) . $str2;
  }

  /**
   * Decode token.
   *
   * @param string $str
   *   The string to be decoded.
   * @param int $expire
   *   Token expire.
   *
   * @return string
   *   Decoded token.
   */
  public function decodeToken($str, $expire = 30) {
    $rs = substr($str, 0, 4);
    $middle = substr($str, 0, 14);
    $rs2 = substr($str, 14, 8);
    return ($middle == $rs . substr(md5($rs . self::TOKEN_SECRET), 0, 10)) && ($_SERVER['REQUEST_TIME'] < $rs + hexdec($rs2) + $expire + 6000);
  }

  /**
   * 加密解密, 生成随机字符串，适用于存储密码等.
   *
   * @param string $tex
   *   The String to be encode or decode.
   * @param string $type
   *   Operate type: encode or decode.
   *
   * @return string
   *   Result.
   */
  public function encodePass($tex, $type = 'encode') {
    $key = 'VeryC&amp;&CLOUd#!(#$$*%#$';
    $chrArr = [
      'a',
      'b',
      'c',
      'd',
      'e',
      'f',
      'g',
      'h',
      'i',
      'j',
      'k',
      'l',
      'm',
      'n',
      'o',
      'p',
      'q',
      'r',
      's',
      't',
      'u',
      'v',
      'w',
      'x',
      'y',
      'z',
      'A',
      'B',
      'C',
      'D',
      'E',
      'F',
      'G',
      'H',
      'I',
      'J',
      'K',
      'L',
      'M',
      'N',
      'O',
      'P',
      'Q',
      'R',
      'S',
      'T',
      'U',
      'V',
      'W',
      'X',
      'Y',
      'Z',
      '0',
      '1',
      '2',
      '3',
      '4',
      '5',
      '6',
      '7',
      '8',
      '9',
    ];
    if ($type == "decode") {
      if (strlen($tex) < 14) {
        return FALSE;
      }
      $verity_str = substr($tex, 0, 8);
      $tex = substr($tex, 8);
      if ($verity_str != substr(md5($tex), 0, 8)) {
        return FALSE;
      }
    }
    $key_b = $type == "decode" ? substr($tex, 0, 6) : $chrArr[rand() % 62] . $chrArr[rand() % 62] . $chrArr[rand() % 62] . $chrArr[rand() % 62] . $chrArr[rand() % 62] . $chrArr[rand() % 62];
    $rand_key = $key_b . $key;
    $rand_key = md5($rand_key);
    $tex = $type == "decode" ? base64_decode(substr($tex, 6)) : $tex;
    $texlen = strlen($tex);
    $reslutstr = "";
    for ($i = 0; $i < $texlen; $i++) {
      $reslutstr .= $tex{$i} ^ $rand_key{$i % 32};
    }
    if ($type != "decode") {
      $reslutstr = trim($key_b . base64_encode($reslutstr), "==");
      $reslutstr = substr(md5($reslutstr), 0, 8) . $reslutstr;
    }
    return $reslutstr;
  }

  /**
   * 获取当前用户的ID.
   */
  public function getCurrentUid() {
    return \Drupal::service('session')->get('userID');
  }

  /**
   * Get Current User, if anonymous, use token to load user.
   *
   * @param array $data
   *   Data.
   *
   * @return mixed
   */
  public function getCurrentUser($data = []) {
    $currentUser = \Drupal::currentUser();
    if (!$currentUser || $currentUser->isAnonymous()) {
      $session_uid = $this->getCurrentUid();
      if ($session_uid) {
        $uid = (int) $session_uid;
      }
      else {
        $token = isset($data['token']) ? $data['token'] : '';
        if (empty($token)) {
          if ($this->request) {
            $bearer = $this->request->headers->get('Authorization');
            $token = array_pop(explode(' ', $bearer));
            if (empty($token)) {
              $token = $this->request->get('token');
            }
          }
        }
        if (empty($token)) {
          return FALSE;
        }
        $tokens = json_decode($this->decrypt($token), TRUE);
        if (empty($tokens) || !is_array($tokens)) {
          return FALSE;
        }

        $now_time = time();
        $expire = isset($tokens['expire']) ? $now_time - $tokens['expire'] : 0;
        if ($expire > 0) {
          return FALSE;
        }
        $uid = isset($tokens['userID']) ? $tokens['userID'] : 0;
      }

      if (!$uid) {
        return FALSE;
      }

      // 尝试根据uid获取username
      $username = '';
      if ($this->database->schema()->tableExists('cloud_user')) {
        $vdb = \Drupal::service('cloud_system.database');
        $cloud_user = $vdb->getRow('cloud_user', ['uid' => $uid], ['uid', 'name']);
        if (!empty($cloud_user) && !empty($cloud_user->name)) {
          $username = $cloud_user->name;
        }
      }
      else {
        $currentUser = User::load($uid);
        if (!empty($currentUser)) {
          $username = $currentUser->getAccountName();
        }
      }

      if (empty($username)) {
        return FALSE;
      }
    }
    else {
      $uid = $currentUser->id();
      $username = $currentUser->getAccountName();
    }

    return [
      'uid' => $uid,
      'username' => $username
    ];
  }

  /**
   * Get random number.
   *
   * @param int $length
   *   The length of this random number.
   *
   * @return string
   *   The random number.
   */
  public function getRandomNumber($length = 6) {
    $chars = '1234567890';
    $random_string = '';

    while (strlen($random_string) < $length) {
      $random_string .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    return $random_string;
  }

  /**
   * Get data from cache.
   *
   * @param string $cacheKey
   *   Cache key.
   * @param string $class
   *   Class.
   * @param string $fun
   *   Function.
   * @param array $request
   *   Parameters.
   * @param int $expire
   *   Expire.
   *
   * @throws $e
   *
   * @return array
   *   Result.
   */
  public function getCachedData($cacheTags, $class, $fun, $data, $expire = self::CACHE_EXPIRE) {
    $cache = \Drupal::cache();
    $user = $this->getCurrentUser();
    $cacheKey = $fun . '_' . md5(serialize($data));
    if (isset($user['uid'])) {
      $cacheKey .= $user['uid'];
    }

    $uid = $this->getCurrentUid();
    if ($uid) {
      $cacheKey .= md5($uid);
    }

    $cache_resources = $cache->get($cacheKey);
    $cacheTags = is_string($cacheTags) ? [$cacheTags] : $cacheTags;
    if (!$cache_resources) {
      try {
        $return = call_user_func([$class, $fun], $data);
        if (isset($return['code']) && $return['code'] == 1) {
          $cache->set($cacheKey, $return, time() + $expire, $cacheTags);
        }
        return $return;
      } catch (\Exception $e) {
        throw $e;
      }
    }
    else {
      return $cache_resources->data;
    }
  }

  /**
   * Generate a uuid string.
   */
  public function getUuid($prefix = '') {
    $base32 = array(
      'a',
      'b',
      'c',
      'd',
      'e',
      'f',
      'g',
      'h',
      'i',
      'j',
      'k',
      'l',
      'm',
      'n',
      'o',
      'p',
      'q',
      'r',
      's',
      't',
      'u',
      'v',
      'w',
      'x',
      'y',
      'z',
      '0',
      '1',
      '2',
      '3',
      '4',
      '5',
    );

    $hex = md5(mt_rand() . ':' . microtime(TRUE));
    $hexLen = strlen($hex);
    $subHexLen = $hexLen / 8;
    $output = [];

    for ($i = 0; $i < $subHexLen; $i++) {
      $subHex = substr($hex, $i * 8, 8);
      $int = 0x3FFFFFFF & hexdec($subHex);
      $out = '';
      for ($j = 0; $j < 6; $j++) {
        $val = 0x0000001F & $int;
        $out .= $base32[$val];
        $int = $int >> 5;
      }
      $output[] = $out;
    }
    $output = $output[0] . $output[1];
    $return = substr($output, 0, 8);
    $return = $prefix . $return;
    return $return;
  }

  /**
   * Pager.
   *
   * @param array $data
   *   分页数组.
   * @param int $page
   *   当前页码.
   * @param int $limit
   *   每页的数量.
   * @param int $real_total
   *   数据总数.
   *
   * @return array $return
   *   pager.
   */
  public function pagerArray($data, $page, $limit, $real_total = 0) {
    $created = date('Y年m月d日 G:i:s', time());
    if (is_array($data) && !empty($data)) {
      $offset = ($page - 1) * $limit;
      $total = !empty($real_total) ? $real_total : count($data);
      $totalpage = ($total % $limit == 0) ? $total / $limit : ceil($total / $limit);
      $slice = array_slice($data, $offset, $limit);
      $return = [
        'code' => 1,
        'message' => '操作完成',
        'data' => $slice,
        'limit' => $limit,
        'page' => $page,
        'totalpage' => $totalpage,
        'total' => $total,
        'created' => $created,
      ];
    }
    else {
      $return = [
        'code' => 0,
        'message' => '暂无数据',
        'created' => $created,
        'total' => 0,
      ];
    }
    return $return;
  }

  /**
   * Runs all the enabled filters on a piece of text.
   *
   * @param string $text
   *   The text to be filtered.
   * @param string $format_type
   *   The type the filter format to be used to filter the
   *   text. Defaults text.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The filtered text.
   *
   * @see check_markup()
   */
  public function checkMarkup($text, $format_type = 'text') {
    if ($format_type == 'html') {
      $purifier_text = check_markup($text, 'htmlpurifier');
      if (!empty($purifier_text)) {
        return $purifier_text->__toString();
      }

      return $purifier_text;
    }

    return check_markup($text)->__toString();
  }

  /**
   * Get agent info by domain.
   *
   * @param string $domain
   *   代理商的自定义前台域名.
   *
   * @return array $agent
   *   agengt info array.
   */
  public function getAgentByDomain($domain = '') {
    $agent = [];
    if (empty($domain)) {
      return $agent;
    }
    $cache = \Drupal::cache();
    $cacheKey = md5($domain);
    $cache_resources = $cache->get($cacheKey);
    if (empty($cache_resources)) {
      if (FALSE !== strpos($domain, ':')) {
        $domain = array_shift(explode(':', $domain));
      }
      $send_data = ['website' => $domain];
      $options = [
        'method' => 'POST',
        'body' => $send_data,
      ];
      $agent_result = $this->httpRequest($this->getApiUrl() . 'profile/agentList', $options);
      if ($agent_result) {
        $agent_result = json_decode($agent_result, TRUE);
        if (isset($agent_result['code']) && ($agent_result['code'] == 1)) {
          $agent = $agent_result['data'][0];
          $cache->set($cacheKey, $agent, time() + 86400);
        }
        else {
          // 根据根域获取代理商的信息
          $root_domain = $this->getRootDomain($domain);
          $agent = $this->getAgentByDomain($root_domain);
        }
      }
    }
    else {
      $agent = $cache_resources->data;
    }
    return $agent;
  }

  /**
   * Get the root domain of the currently accessed hostname.
   *
   * @param  string $domain
   *   The string to be match against the domain.
   *
   * @return string|bool
   */
  public function getRootDomain($domain) {
    if (preg_match(
      '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i',
      $domain,
      $regs)) {
      return $regs['domain'];
    }
    return FALSE;
  }

  /**
   * 保存日志.
   *
   * @param int $op
   *   操作类型.
   * @param string $type
   *   日志类型.
   * @param string $schema
   *   表名.
   * @param string $title
   *   标题.
   * @param string $message
   *   内容.
   * @param int $uid
   *   操作人.
   * @param int $rid
   *   资源ID
   *
   * @return bool|\Drupal\Core\Database\StatementInterface|int|null
   * @throws \Exception
   */
  public function logSave($op, $type, $schema, $title, $message, $uid = 0, $rid = 0) {
    if (!$uid) {
      $user = $this->getCurrentUser();
      if (empty($user)) {
        $uid = $this->getCurrentUid();
      }
      else {
        $uid = $user['uid'];
      }
    }

    if ($op > 100) {
      return FALSE;
    }

    if (empty($type) || empty($title) || empty($schema) || empty($message)) {
      return FALSE;
    }

    // get ip address
    $ip = $this->realIp();
    $fields = [
      'uid',
      'op',
      'type',
      'ip',
      'title',
      'content',
      'created',
    ];

    $values = [
      $uid,
      $op,
      $type,
      $ip,
      $title,
      $message,
      time(),
    ];

    if ($rid) {
      $fields[] = 'rid';
      $values[] = $rid;
    }

    try {
      $return = $this->database->insert($schema)
        ->fields($fields)
        ->values($values)
        ->execute();
      return $return;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * 构造日志表格.
   *
   * @param $messages
   *   日志信息.
   *
   * @return string
   *   日志表格.
   */
  public function logTable($messages) {
    if (empty($messages)) {
      return "";
    }

    $html = '<table class="table table-bordered">';
    $html .= '<tr>';
    $html .= '<th width="20%">字段</th>';
    $html .= '<th width="40%">修改前</th>';
    $html .= '<th width="40%">修改后</th>';
    $html .= '</tr>';

    foreach ($messages as $message) {
      $html .= '<tr>';
      $html .= '<td>' . (isset($message[0]) ? $message[0] : '') . '</td>';
      $html .= '<td>' . (isset($message[1]) ? $message[1] : '') . '</td>';
      $html .= '<td>' . (isset($message[2]) ? $message[2] : '') . '</td>';
      $html .= '</tr>';
    }

    $html .= '</table>';

    return $html;
  }

}
