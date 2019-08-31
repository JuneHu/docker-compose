<?php

namespace Drupal\cloud_system;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Promise;

/**
 * Concurrent HTTP requests.
 */
class MultiHttpRequest {
  /**
   * @var array
   */
  protected $failed = [];

  /**
   * @var array
   */
  protected $success = [];

  /**
   * @var int
   */
  protected $timeout = 5;

  /**
   * @var int
   */
  protected $connectTimeout = 2;

  /**
   * @var int
   */
  protected $concurrency = 8;

  /**
   * @var int
   */
  protected $retries = 2;

  /**
   * @var array
   */
  protected $urlList = [];

  /**
   * @var array
   */
  protected $headers = [];

  /**
   * @var string
   */
  protected $method;

  /**
   * @var array
   */
  protected $bale = [];

  protected $is_response_header;

  /**
   * @var array
   */
  protected $concurrencyResponseData = [];

  /**
   * @return int
   */
  public function getFailed() {
    return $this->failed;
  }

  /**
   * @return int
   */
  public function getSuccess() {
    return $this->success;
  }

  /**
   * @return int
   */
  public function getConcurrency() {
    return $this->concurrency;
  }

  /**
   * @param int $concurrency
   *
   * @return $this
   */
  public function setConcurrency(int $concurrency) {
    $this->concurrency = $concurrency;

    return $this;
  }

  /**
   * @return int
   */
  public function getTimeout() {
    return $this->timeout;
  }

  /**
   * @param int $timeout
   *
   * @return $this
   */
  public function setTimeout(int $timeout) {
    $this->timeout = $timeout;

    return $this;
  }

  /**
   * @return int
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * @param string $method
   *
   * @return $this
   */
  public function setMethod($method) {
    $this->method = $method;

    return $this;
  }

  /**
   * @return array
   */
  public function getOptions() {
    return $this->headers;
  }

  /**
   * response的时候是否需要返回header.
   */
  public function setResponseHeader($is_response_header = FALSE) {
    $this->is_response_header = $is_response_header;
    return $this;
  }

  public function getResponseHeader() {
    return $this->is_response_header;
  }

  /**
   * @param string $options
   *
   * @return $this
   */
  public function setOptions($options = []) {
    $nowTime = time();
    $accept = $options['accept'] ?? 'application/json';
    $content_type = $options['content-type'] ?? 'application/json';
    $no_content_type = isset($options['no_content_type']) ? (int) $options['no_content_type'] : 0;
    $body = $options['body'] ?? [];
    $authorization = $options['authorization'] ?? '';

    // For ecs instance request.
    $cloud_host = isset($options['CloudHost']) ? $options['CloudHost'] : '';
    $cookie = isset($options['Cookie']) ? $options['Cookie'] : '';
    $x_request_date = isset($options['x-request-date']) ? $options['x-request-date'] : '';

    // CMCC头
    $CMCDN_Auth_Token = isset($options['CMCDN-Auth-Token']) ? $options['CMCDN-Auth-Token'] : '';
    $HTTP_X_CMCDN_Signature = isset($options['HTTP-X-CMCDN-Signature']) ? $options['HTTP-X-CMCDN-Signature'] : '';

    $headers = [
      'headers' => [
        'User-Agent' => 'API/2.0 JUST DO IT',
        'Date' => gmdate('D, d M Y H:i:s \G\M\T', $nowTime),
        'Accept-Encoding' => 'gzip',
        'Accept' => $accept,
        'Content-Type' => $content_type,
      ],
      'verify' => FALSE,
    ];

    if (isset($options['headers']) && !empty($options['headers'])) {
      $headers['headers'] = $options['headers'];
    }

    if (!empty($no_content_type)) {
      unset($headers['headers']['Content-Type']);
    }

    if (!empty($cloud_host)) {
      $headers['headers']['CloudHost'] = $cloud_host;
    }

    if (!empty($cookie)) {
      $headers['headers']['Cookie'] = $cookie;
    }

    if (!empty($authorization)) {
      $headers['headers']['Authorization'] = $authorization;
    }

    if (!empty($x_request_date)) {
      $headers['headers']['x-request-date'] = $x_request_date;
    }

    if (!empty($CMCDN_Auth_Token)) {
      $headers['headers']['CMCDN-Auth-Token'] = $CMCDN_Auth_Token;
    }

    if (!empty($HTTP_X_CMCDN_Signature)) {
      $headers['headers']['HTTP-X-CMCDN-Signature'] = $HTTP_X_CMCDN_Signature;
    }

    if (FALSE !== strpos($content_type, 'application/json')) {
      $headers['body'] = is_array($body) ? json_encode($body) : $body;
    }
    else {
      $headers['body'] = $body;
    }

    if (FALSE !== strpos($content_type, 'application/x-www-form-urlencoded') && !empty($body)) {
      unset($headers['body']);
      $headers['form_params'] = $body;
    }

    $this->headers = $headers;

    return $this;
  }

  /**
   * @return int
   */
  public function getRetries() {
    return $this->retries;
  }

  /**
   * @param int $retries
   *
   * @return $this
   */
  public function setRetries(int $retries) {
    $this->retries = $retries;

    return $this;
  }

  /**
   * @return int
   */
  public function getConnectTimeout() {
    return $this->connectTimeout;
  }

  /**
   * @param int $connectTimeout
   *
   * @return $this
   */
  public function setConnectTimeout(int $connectTimeout) {
    $this->connectTimeout = $connectTimeout;

    return $this;
  }

  /**
   * Create closure which returns all prepared calls for chunk actions.
   *
   * @return \Closure
   */
  protected function createClosureForRequestsList() {
    $requests = function ($client, $urls) {
      foreach ($urls as $url) {
        yield function () use ($client, $url) {
          return $client->requestAsync(
            $this->method,
            $url,
            $this->headers
          );
        };
      }
    };

    return $requests;
  }

  /**
   * Create http client with retry handler.
   *
   * @return Client
   */
  protected function createHttpClientWithRetryHandler() {
    $stack = HandlerStack::create();
    $stack->push(Middleware::retry($this->createRetryHandler()));

    return new Client([
      'handler' => $stack,
      'connect_timeout' => $this->connectTimeout,
      'timeout' => $this->timeout,
    ]);
  }

  /**
   *
   * Performs an concurrency http request.
   *
   * @param $urls
   */
  public function request($urls) {
    $this->concurrencyResponseData = [];
    $this->urlList = $urls;
    /**
     * @var \Closure $requests
     */
    $requests = $this->createClosureForRequestsList();

    /**
     * Create http client with RetryHandler.
     */
    $client = $this->createHttpClientWithRetryHandler();

    if (!isset($this->headers)) {
      $this->setOptions();
    }

    $this->concurrency = count($urls);

    $pool = new Pool(
      $client,
      $requests(
        $client,
        $urls,
        $this->method,
        $this->headers
      ), [
      'concurrency' => $this->concurrency,
      'fulfilled' => function (Response $response, $index) use ($urls) {
        $body = (string) $response->getBody();
        $headers = $response->getHeaders();
        $url = isset($urls[$index]) ? $urls[$index] : '';
        if (!empty($url)) {
          $this->concurrencyResponseData['success'][$url] = $body;

          if ($this->getResponseHeader()) {
            $this->concurrencyResponseData['success']['headers'][$url] = $headers;
          }
        }
      },
      'rejected' => function ($reason, $index) use ($urls) {
        if ($reason instanceof \GuzzleHttp\Exception\ClientException) {
          $error = $reason->getResponse()->getBody()->getContents();
        }
        else {
          $error = $reason->getMessage();
        }

        $url = isset($urls[$index]) ? $urls[$index] : '';
        // Send mail.
        $dblogEnable = \Drupal::config('cloud_system.settings')
          ->get('dblog_enable');
        if ($dblogEnable) {
          $curl_uri = $urls[$index];
          $log_str = $error;

          $errorBody = 'API: ' . PHP_EOL . $curl_uri
            . PHP_EOL . '<b>ERROR:</b> '
            . PHP_EOL . $log_str;

          $mail = \Drupal::config('cloud_system.email_config')
            ->get('default_mail');
          \Drupal::service('cloud_system.base')->sendMail($mail, [
            'subject' => 'API Multi Request Error',
            'body' => $errorBody,
          ]);
        }

        if (!empty($url)) {
          $this->concurrencyResponseData['failed'][$url] = $error;
        }
      },
    ]);

    // Initiate the transfers and create a promise.
    $promise = $pool->promise();

    // Force the pool of requests to complete.
    $promise->wait();

    return $this->concurrencyResponseData;
  }

  /**
   * Create a retry handler for guzzle 6.
   *
   * @return \Closure
   */
  protected function createRetryHandler() {
    return function (
      $retries,
      Request $request,
      Response $response = NULL,
      RequestException $exception = NULL
    ) {
      // set max retries from config
      $maxRetries = $this->retries;

      if ($retries >= $maxRetries) {
        return FALSE;
      }

      if (!($this->isServerError($response) || $this->isConnectError($exception))) {
        return FALSE;
      }

      /*$this->log(sprintf(
        'Retrying %s %s %s/%s, %s',
        $request->getMethod(),
        $request->getUri(),
        $retries + 1,
        $maxRetries,
        $response ? 'status code: ' . $response->getStatusCode() : $exception->getMessage()
      ));*/
      return TRUE;
    };
  }

  /**
   * @param string $message
   */
  protected function log(string $message) {
    $time = new \DateTime('now');

    echo $time->format('c') . ' ' . $message . PHP_EOL;
  }

  /**
   * @param Response $response
   *
   * @return bool
   */
  protected function isServerError(Response $response = NULL) {
    return $response && $response->getStatusCode() >= 400;
  }

  /**
   * @param RequestException $exception
   *
   * @return bool
   */
  protected function isConnectError(RequestException $exception = NULL) {
    return $exception instanceof ConnectException;
  }

  /**
   * Prepare Concurrent request, Body, header may be different.
   *
   * @param array $urls
   * @param string $method
   * @param array $header
   * @param array $body
   *
   * @return $this
   */
  public function add($urls = [], $method = 'GET', $body = [], $header = []) {
    $nowTime = time();
    $accept = $header['accept'] ?? 'application/json';
    $content_type = $header['content-type'] ?? 'application/json; charset=utf-8';
    $authorization = $header['authorization'] ?? '';

    $headers = [
      'headers' => [
        'User-Agent' => 'API/2.0 JUST DO IT',
        'Date' => gmdate('D, d M Y H:i:s T', $nowTime),
        'Accept-Encoding' => 'gzip',
        'Accept' => $accept,
        'Content-Type' => $content_type,
      ],
      'verify' => FALSE,
    ];

    if (isset($header['headers']) && !empty($header['headers'])) {
      $headers['headers'] = $header['headers'];
    }

    if (!empty($authorization)) {
      $headers['headers']['Authorization'] = $authorization;
    }

    if (FALSE !== strpos($content_type, 'application/json') && !empty($body)) {
      $headers['body'] = json_encode($body);
    }

    if (FALSE !== strpos($content_type, 'application/x-www-form-urlencoded') && !empty($body)) {
      $headers['form_params'] = $body;
    }

    $this->bale[] = [
      'urls' => $urls,
      'method' => $method,
      'options' => $headers,
    ];

    return $this;
  }


  /**
   * Send concurrent request.
   */
  public function send() {

    /**
     * Create http client with RetryHandler.
     */
    /**
     * Create http client with RetryHandler.
     */
    $client = $this->createHttpClientWithRetryHandler();

    $requestPromises = [];

    foreach ($this->bale as $bale) {
      foreach ($bale['urls'] as $url) {
        $md5 = md5(serialize($bale) . $url);
        $requestPromises[$url . '####' . $md5] = $client->requestAsync($bale['method'], $url, $bale['options']);
      }
    }

    // Wait for the requests to complete, even if some of them fail.
    $results = Promise\settle($requestPromises)->wait();

    $error = '';
    foreach ($results as $url => $result) {
      $url = substr($url, 0, strpos($url, '####'));
      if ($result['state'] === 'fulfilled') {
        $response = (string) $result['value']->getBody();
        $this->concurrencyResponseData['success'][$url][] = $response;
      }
      else {
        if ($result['state'] === 'rejected') {
          $reason = (string) $result['reason']->getMessage();
          $this->concurrencyResponseData['failed'][$url][] = $reason;
          $error .= "URL：" . $url . "，REASON：" . $reason . "\r\n";
        }
        else {
          $this->concurrencyResponseData['failed'][$url][] = 'ERROR: unknown exception';
          $error .= "URL：" . $url . "，REASON：unknown exception。 \r\n";
        }
      }
    }

    if (!empty($error)) {
      // Send mail.
      $dblogEnable = \Drupal::config('cloud_system.settings')
        ->get('dblog_enable');
      if ($dblogEnable) {
        $mail = \Drupal::config('cloud_system.email_config')
          ->get('default_mail');
        \Drupal::service('cloud_system.base')->sendMail($mail, [
          'subject' => 'API Multi Request Error',
          'body' => $error,
        ]);
      }
    }

    return $this->concurrencyResponseData;
  }
}
