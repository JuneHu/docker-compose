<?php
/**
 * @file
 * Contains \Drupal\cloud_system\CloudSystemHttpKernelMiddleware.
 */

namespace Drupal\cloud_system;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Http kernel Middleware.
 *
 * @package Drupal\cloud_system
 */
class CloudSystemHttpKernelMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $app;

  /**
   * Cloud System manager instance.
   *
   * @var \Drupal\cloud_system\CloudSystemManagerInterface
   */
  protected $manager;

  /**
   * Cloud System base instance.
   *
   * @var \Drupal\cloud_system\CloudSystemBase
   */
  protected $base;

  /**
   * Constructs Cloud System Middleware.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $app
   *   The wrapper HTTP kernel.
   * @param \Drupal\cloud_system\CloudSystemManagerInterface $manager
   *   The cloud system manager interface.
   * @param \Drupal\cloud_system\CloudSystemBase $base
   *   The cloud system base interface.
   */
  public function __construct(HttpKernelInterface $app, CloudSystemManagerInterface $manager, CloudSystemBase $base) {
    $this->app = $app;
    $this->manager = $manager;
    $this->base = $base;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    // Validate if the Accept header is existed.
    // 由于支付宝回调没有Accept头，暂时去掉这个校验.
    /*$accept_header = $this->manager->hasAcceptHeader($request);
    if (empty($accept_header)) {
      $message = 'Missing header Accept.';
      return $this->manager->respond($message);
    }*/

    $enabled = $this->manager->isEnabled();
    // Only run the api service if it's enabled.
    if ($enabled === TRUE) {
      $service_request = $this->manager->isApiRequest($request);
      // If this is a Web Service request then run the rate limiter service.
      if ($service_request === TRUE) {
        // If this is a service request and the rate limiter service is enabled
        // then get all the settings and send it to rate limiter manager.
        $bearer = $request->headers->get('Authorization');
        $token = '';
        if (!empty($bearer)) {
          $bearer_arr = explode(' ', $bearer);
          if (!empty($bearer_arr)) {
            $token = array_pop($bearer_arr);
          }
        }
        if (empty($token)) {
          return $this->manager->respond();
        }
        $tokens = json_decode($this->base->decrypt($token), TRUE);
        if (empty($tokens) || !is_array($tokens)) {
          $message = 'Invalid authentication token.';
          return $this->manager->respond($message);
        }

        $now_time = time();
        $expire = isset($tokens['expire']) ? $now_time - $tokens['expire'] : 0;
        if ($expire >= 0) {
          $message = 'The authentication token has been expired.';
          return $this->manager->respond($message);
        }

        // For voss or verycloud portal.
        $userID = isset($tokens['userID']) ? (int) $tokens['userID'] : 0;
        if ($userID) {
          // TODO: Validation user privilege.
          // Save user id to session.
          \Drupal::service('session')->set('userID', $userID);
        }
      }
    }
    return $this->app->handle($request, $type, $catch);
  }

}
