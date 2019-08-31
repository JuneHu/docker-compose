<?php
/**
 * @file
 * Contains \Drupal\cloud_system\CloudSystemManagerInterface.
 */

namespace Drupal\cloud_system;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface to CloudSystemManager.
 *
 * @package Drupal\cloud_system
 */
interface CloudSystemManagerInterface {

  /**
   * Method checks if the api service is enabled or not.
   *
   * @return bool
   *   Returns TRUE if api service is enabled or FALSE.
   */
  public function isEnabled();

  /**
   * Method checks if the request is for Service endpoint URL or not.
   *
   * First check if the request is an AJAX request or not. If not AJAX request
   * then if the Request header has "text/html" we can assume that this is a
   * basic drupal page access.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request instance.
   *
   * @return bool
   *   Returns TRUE if this is a service request or FALSE.
   */
  public function isApiRequest(Request $request);

  /**
   * Checks if the request has accept header.
   *
   * @return bool
   *   Returns TRUE if has accept header or FALSE.
   */
  public function hasAcceptHeader(Request $request);

  /**
   * Method responds if the api service validated.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
   *   The proper response after checking the accept header.
   */
  public function respond();

}
