<?php

namespace Drupal\Tests\cloud_system\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Create http request and other functions if works.
 *
 * @group cloud_system
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class CloudSystemTest extends BrowserTestBase {
  /**
   * The user handle.
   *
   * @var \Drupal\user\Entity\User.
   */
  protected $user;

  /**
   * The cloudSystemBase handle.
   *
   * @var \Drupal\cloud_system\CloudSystemBase.
   */
  protected $base;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['cloud_system'];


  /**
   * Associative array of urls strings to test.
   *
   * Keys are the color string and values are a Boolean set to TRUE for valid
   * colors.
   *
   * @var array
   */
  protected $urlTests;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Array filled with valid and not valid color values.
    $this->urlTests = [
      /*'http://60.174.242.40/_vstats',
      'http://112.245.16.151/_vstats',
      'http://122.13.86.83/_vstats',
      'http://218.6.154.180/_vstats',
      'http://171.107.82.41/_vstats',
      'http://202.111.173.104/_vstats',
      'http://119.84.87.242/_vstats',
      'http://211.161.101.7/_vstats',
      'http://61.183.52.87/_vstats',
      'http://116.31.120.13/_vstats',
      'http://221.11.62.39/_vstats',
      'http://222.175.114.218/_vstats',
      'http://113.6.248.5/_vstats',
      'http://221.204.173.202/_vstats',
      'http://222.140.154.139/_vstats',
      'http://112.21.165.170/_vstats',
      'http://222.73.199.105/_vstats',
      'http://60.191.139.121/_vstats',
      'http://36.250.226.106/_vstats',
      'http://59.53.95.149/_vstats',
      'http://175.6.128.230/_vstats',
      'http://222.222.18.8/_vstats',
      'http://113.107.207.232/_vstats',
      'http://27.209.183.42/_vstats',
      'http://221.14.149.236/_vstats',
      'http://113.107.207.244/_vstats',
      'http://42.4.49.201/_vstats',
      'http://61.160.239.148/_vstats',
      'http://122.188.107.246/_vstats',*/
      'http://www.verycloud.com.cn/cloud/product/js/get_cdn_flow',
      'http://www.verycloud.com.cn/cloud/front/js/get_login_status',
      'http://www.verycloud.com.cn/api/',
      'http://www.verycloud.com.cn/api/test',
    ];

    $this->user = $this->drupalCreateUser();

    // $http_client_mock = $this->getMock('\GuzzleHttp\ClientInterface');.
    $this->base = \Drupal::service('cloud_system.base');
    // fwrite(STDERR, print_r($this->base->httpRequest(), TRUE));.
  }

  /**
   * Tests the encrypt && decrypt functionality.
   */
  public function testencrypt() {
    $encrypt_string = $this->base->encrypt('test');
    $decrypt_string = $this->base->decrypt($encrypt_string);
    //$this->assertNotEmpty($decrypt_string);
  }

  /**
   * Tests the realIp functionality.
   */
  public function testrealIp() {
    $return = $this->base->realIp();
    //$this->assertNotEmpty($return);
  }

  /**
   * Tests the httpRequest functionality.
   */
  public function testHttpRequest() {
    $return = $this->base->httpRequest('http://www.verycloud.com.cn/API/', ['method' => 'POST', 'body' => ["test" => 1]]);
    fwrite(STDOUT, $return);
    // $this->assertNotEmpty($return);
    //$this->assertNotEmpty($return);

    $return = $this->base->httpRequest('http://www.verycloud.com.cn/API/test1/', ['method' => 'POST', 'body' => ["test" => 1]]);
    //$this->assertTrue($return);
  }

  /**
   * Test the getAccessToken functionality.
   */
  public function testGetAccessToken() {
    $token = $this->base->getAccessToken();
    //$this->assertEquals(FALSE, $token);
  }

  public function testOauthAuthorizeApi() {
    $token = $this->base->encrypt(json_encode([
      'expire' => time() + 3600,
    ]));
    $return = $this->base->httpRequest('http://127.0.0.1:8080/API/OAuth/authorize', [
      'method' => 'POST',
      'body' => [
        "version" => 'v2',
        'token' => $token,
      ]
    ]);
    $this->assertEquals(TRUE, $return);
  }

}
