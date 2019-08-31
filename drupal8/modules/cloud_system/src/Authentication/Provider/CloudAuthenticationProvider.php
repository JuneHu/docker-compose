<?php

namespace Drupal\cloud_system\Authentication\Provider;

use Drupal\cloud_system\CloudSystemBaseInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\UserSession;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class CloudAuthenticationProvider.
 *
 * @package Drupal\cloud_system\Authentication\Provider
 */
class CloudAuthenticationProvider implements AuthenticationProviderInterface {
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The system base manager.
   *
   * @var \Drupal\cloud_system\CloudSystemBaseInterface
   */
  protected $cloudSystemBase;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\cloud_system\CloudSystemBaseInterface $system_base
   *   The cloud system base service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, CloudSystemBaseInterface $system_base) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->cloudSystemBase = $system_base;
  }

  /**
   * Checks whether suitable authentication credentials are on the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if authentication credentials suitable for this provider are on the
   *   request, FALSE otherwise.
   */
  public function applies(Request $request) {
    // If path contains 'API', must be authenticated.
    $current_path = \Drupal::service('path.current')->getPath();
    if (false !== strpos($current_path, '/API/')) {
      return TRUE;
    }

    // If you return TRUE and the method Authentication logic fails,
    // you will get out from Drupal navigation if you are logged in.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // If the user already login, skip.
    $currentUser = $this->getUserFromSession($request->getSession());
    if (!empty($currentUser) && !$currentUser->isAnonymous()) {
      return $currentUser;
    }

    $current_path = \Drupal::service('path.current')->getPath();
    $ignore_path = \Drupal::config('cloud_system.settings')->get('ignore_apis');
    if (!empty($ignore_path)) {
      foreach ($ignore_path as $ignore) {
        if ($current_path == $ignore['uri'] && strtoupper($request->getMethod()) == $ignore['method']) {
          return $this->entityTypeManager->getStorage('user')->load(0);
        }
      }
    }

    // Validate access permission.
    $bearer = $request->headers->get('Authorization');
    $bearers = explode(' ', $bearer);
    $authorization = array_pop($bearers);
    if (empty($authorization)) {
      $authorization = $request->get('token');
    }

    $auth_string = $this->cloudSystemBase->decrypt($authorization);
    $auth_manager = json_decode($auth_string);

    if (empty($auth_manager)) {
      return NULL;
    }

    // Auth数组由过期时间和token组成.
    if (!empty($auth_manager)) {
      if (!isset($auth_manager->expire)) {
        return NULL;
      }

      $timeout = $auth_manager->expire;
      if ($timeout < time()) {
        return NULL;
      }
    }

    // Return Anonymous user.
    return $this->entityTypeManager->getStorage('user')->load(0);
  }

  /**
   * Returns the UserSession object for the given session.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   *
   * @return \Drupal\Core\Session\AccountInterface|NULL
   *   The UserSession object for the current user, or NULL if this is an
   *   anonymous session.
   */
  protected function getUserFromSession(SessionInterface $session) {
    if ($uid = $session->get('uid')) {
      // @todo Load the User entity in SessionHandler so we don't need queries.
      // @see https://www.drupal.org/node/2345611
      $values = $this->cloudSystemBase->database
        ->query('SELECT * FROM {users_field_data} u WHERE u.uid = :uid AND u.default_langcode = 1', [':uid' => $uid])
        ->fetchAssoc();

      // Check if the user data was found and the user is active.
      if (!empty($values) && $values['status'] == 1) {
        // Add the user's roles.
        $rids = $this->cloudSystemBase->database
          ->query('SELECT roles_target_id FROM {user__roles} WHERE entity_id = :uid', [':uid' => $values['uid']])
          ->fetchCol();
        $values['roles'] = array_merge([AccountInterface::AUTHENTICATED_ROLE], $rids);

        return new UserSession($values);
      }
    }

    // This is an anonymous session.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {}

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      $event->setException(new UnauthorizedHttpException('No authentication credentials provided.', $exception));
      return TRUE;
    }
    return FALSE;
  }

}
