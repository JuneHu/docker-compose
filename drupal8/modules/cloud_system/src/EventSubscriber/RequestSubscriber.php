<?php

namespace Drupal\cloud_system\EventSubscriber;

use Drupal\cloud_system\CloudSystemUtils;
use Drupal\Core\Access\AccessException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use \Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatch;

/**
 * 用于拦截请求.
 */
class RequestSubscriber implements EventSubscriberInterface {


  /**
   * The front path.
   */
  const FRONT_PATH = '/';

  /**
   * The console path.
   */
  const CONSOLE_PATH = '/profile/console';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new R4032LoginSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * 如果使用get方法请求接口，在url后统一加_format=json参数.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The response event, which contains the current request.
   */
  public function onRequest(GetResponseEvent $event) {
    $path = $event->getRequest()->getUri();
    $method = $event->getRequest()->getMethod();
    if (FALSE !== strpos($path, '/API/')) {
      $event->getRequest()->setRequestFormat('json');
    }
  }

  /**
   * Redirects on 403 Access Denied kernel exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the access is denied and redirects to user login page.
   */
  public function onKernelException(GetResponseEvent $event) {
    $request = $event->getRequest();
    $pathInfo = $request->getPathInfo();
    // 后台请求直接返回
    $admin_request_flag = FALSE !== strpos($pathInfo, 'admin');
    if ($admin_request_flag) {
      return;
    }

    $exception = $event->getException();
    if (!($exception instanceof AccessDeniedHttpException) && !($exception instanceof NotFoundHttpException)) {
      return;
    }

    // 非api请求
    $normal_request_flag = FALSE === strpos($pathInfo, 'API');
    if (!$normal_request_flag) {
      return;
    }
    else {
      if ($this->currentUser->isAnonymous() && $pathInfo != self::FRONT_PATH) {
        $event->setResponse(new RedirectResponse(self::FRONT_PATH));
      }
      elseif ($this->currentUser->isAuthenticated() && $pathInfo != self::CONSOLE_PATH) {
        $event->setResponse(new RedirectResponse(self::CONSOLE_PATH));
      }
    }
  }

  /**
   * Handle controller event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
   *   The controller event.
   */
  public function onKernelController(FilterControllerEvent $event) {
    $request = $event->getRequest();

    // If access control is on, check it.
    $accessControl = $this->isAccessControl();
    if ($accessControl) {
      $ip_whitelist = \Drupal::state()->get('CLOUD_IP_BLACKLIST');
      $clientIp = \Drupal::service('cloud_system.base')->realIp();
      if (!empty($ip_whitelist)) {
        $validIps = explode('|', $ip_whitelist);
        if (!in_array($clientIp, $validIps)) {
          throw new AccessDeniedHttpException();
        }
      }
    }

    // Router access permission.
    // BOSS从接口去读取权限.
    $platform = \Drupal::config('cloud_system.settings')->get('platform');
    if ($platform == 'boss') {
      $is_authenticated = \Drupal::service('session')->get('userID');
      if ($this->isEnabled() && $is_authenticated && $is_authenticated != 1) {
        $this->checkAccess($request);
      }
    }
  }

  /**
   * handle some redirect logic.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
    // Remove X-Generator header.
    $response = $event->getResponse();
    $response->headers->remove('X-Generator');
  }

  /**
   * Check access swicth
   *
   * @param null
   * @return boolean
   */
  private function isAccessControl () {
    // Ignore root user.
    if ($this->currentUser->id() == 1) {
      return FALSE;
    }
    $ip_whitelist = (int) \Drupal::config('cloud_system.settings')->get('ip_whitelist');
    return !empty($ip_whitelist);
  }

  /**
   * {@inheritdoc}
   */
  private function isEnabled() {
    // Ignore root user.
    if ($this->currentUser->id() == 1) {
      return FALSE;
    }
    return (bool) \Drupal::config('cloud_system.settings')->get('api_permission');
  }

  /**
   * {@inheritdoc}
   */
  private function getUserRouters($request) {
    return \Drupal::service('plugin.manager.rest')->getInstance([
      'id' => 'user:router:permission',
    ])->get(null, $request)->getContent();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when access checking failed.
   */
  private function matchRequest($router_name, $router_data) {
    $ignore_routes = \Drupal::config('cloud_system.settings')->get('ignore_routes');
    $router_name_dot = str_replace('.', '_', $router_name);
    if (isset($ignore_routes[$router_name_dot])) {
      return TRUE;
    }
    return in_array($router_name, $router_data);
  }

  /**
   * Apply access check service to the route and parameters in the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to access check.
   */
  private function checkAccess($request) {
    // The cacheability (if any) of this request's access check result must be
    // applied to the response.
    $route_match = RouteMatch::createFromRequest($request);
    $route_name = $route_match->getRouteName();
    $path = $route_match->getRouteObject()->getPath();
    $userRoute = $this->getUserRouters($request);
    if (empty($userRoute)) {
      throw new AccessDeniedHttpException(CloudSystemUtils::t('Url %s Access denied.', [$path]));
    }

    $userRoutes = @json_decode($userRoute);
    if ($userRoutes->code != 1) {
      throw new AccessDeniedHttpException(CloudSystemUtils::t('Url %s Access denied.', [$path]));
    }

    $route_data = $userRoutes->data;
    if (!$this->matchRequest($route_name, $route_data)) {
      throw new AccessDeniedHttpException(CloudSystemUtils::t('Url %s Access denied.', [$path]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    /*
     * KernelEvents::CONTROLLER; // The CONTROLLER event occurs once a controller was found for handling a request.
     * KernelEvents::EXCEPTION; // The EXCEPTION event occurs when an uncaught exception appears.
     * KernelEvents::FINISH_REQUEST; //The FINISH_REQUEST event occurs when a response was generated for a request.
     * KernelEvents::REQUEST; // The REQUEST event occurs at the very beginning of request dispatching.
     * KernelEvents::RESPONSE; // The RESPONSE event occurs once a response was created for replying to a request.
     * KernelEvents::TERMINATE; // The TERMINATE event occurs once a response was sent.
     * KernelEvents::VIEW; // The VIEW event occurs when the return value of a controller is not a Response instance.
     */
    $events[KernelEvents::EXCEPTION][] = ['onKernelException'];

    // Subscribe to kernel request with default priority of 0.
    $events[KernelEvents::REQUEST][] = ['onRequest', 10000];

    // Subscribe to kernel controller with default priority of 0.
    $events[KernelEvents::CONTROLLER][] = ['onKernelController', 0];

    // Subscribe to kernel response with default priority of 0.
    // Set to -1 for remove X-Generator header.
    $events[KernelEvents::RESPONSE][] = ['onResponse', -1];
    return $events;
  }

}
