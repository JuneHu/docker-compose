<?php
/**
 * @file
 * Contains \Drupal\cloud_system\CloudSystemManager.
 */

namespace Drupal\cloud_system;

use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\HeaderBag;


/**
 * Cloud System Manager.
 *
 * @package Drupal\cloud_system
 */
class CloudSystemManager implements CloudSystemManagerInterface {

  /**
   * Cloud system configuration storage.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $cloudSystemConfig;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * Class Constructor.
   */
  public function __construct() {
    $this->cloudSystemConfig = \Drupal::config('cloud_system.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->cloudSystemConfig->get('is_api_service');
  }

  /**
   * Method returns an associative array of allowed accept header types.
   *
   * @return array
   *   Associative array of allowd accept header types.
   */
  public static function allowedAcceptTypes() {
    return [
      'application/json' => 'json',
      'application/hal+json' => 'hal_json',
      'application/xml' => 'xml',
      'text/html' => 'html',
      '*/*' => '*/*',
    ];
  }

  /**
   * Method scans the Accept header present in Request header.
   *
   * @param \Symfony\Component\HttpFoundation\HeaderBag $header
   *   The request header instance.
   *
   * @return string
   *   The accept header type.
   */
  private function acceptType($header) {
    $type = NULL;
    $accept = AcceptHeader::fromString($header->get('Accept'));
    foreach (self::allowedAcceptTypes() as $header => $name) {
      if ($accept->has($header)) {
        $type = $name;
        break;
      }
    }
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAcceptHeader(Request $request) {
    $type = $this->acceptType($request->headers);
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function isApiRequest(Request $request) {
    $this->request = $request;
    // Check if this is not a php cli request.
    if (php_sapi_name() === 'cli' || defined('STDIN')) {
      return FALSE;
    }

    if ($request->isMethod('OPTIONS')) {
      return FALSE;
    }

    // The request is not an AJAX request.
    if (!$request->isXmlHttpRequest()) {
      // First check for "_format" query parameter as this is now accepted by
      // Drupal (Accept header based routing got replaced by a query parameter).
      // @see https://www.drupal.org/node/2501221
      $path = $request->getPathInfo();
      $method = strtoupper($request->getMethod());
      $is_exist = md5($path . $method);
      // Ignore some api to verify authorization headers.
      $ignore_path = \Drupal::config('cloud_system.settings')->get('ignore_apis');
      if (!empty($ignore_path)) {
        foreach ($ignore_path as $ignore) {
          $ignore_str = md5(trim($ignore['uri']) . trim($ignore['method']));
          if ($ignore_str == $is_exist) {
            return FALSE;
          }
        }
      }

      // API enable verify.
      if (FALSE !== strpos($path, 'API') && FALSE === strpos($path, 'OAuth2') && FALSE === strpos($path, 'OAuth')) {
        return TRUE;
      }

    }
    elseif ($request->get('ajax_iframe_upload', FALSE)) {
      // Ajax iframe upload should return false.
      return FALSE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function respond($message = '') {
    $info = $message ?: 'Missing header Authorization.';
    // If request header requested for JSON data then respond with JSON.
    return new JsonResponse(['error' => CloudSystemUtils::t($info)], Response::HTTP_UNAUTHORIZED);
  }

}
