<?php

namespace Drupal\cloud_system\EventSubscriber;

use Drupal\cloud_system\CloudSystemBaseInterface;
use Drupal\cloud_system\CloudSystemUtils;
use Drupal\Core\Utility\Error;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

/**
 * Database 500 error exception subscriber.
 *
 * This subscriber will return a minimalist 500 response for HTML requests
 * without running a full page theming operation.
 */
class HttpExceptionSubscriber extends HttpExceptionSubscriberBase {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cloud base.
   *
   * @var \Drupal\cloud_system\CloudSystemBaseInterface
   */
  protected $base;

  /**
   * Constructs a new CloudHttpExceptionSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP Kernel service.
   * @param \Drupal\cloud_system\CloudSystemBaseInterface $cloud_base
   *   The cloud base.
   */
  public function __construct(ConfigFactoryInterface $config_factory, HttpKernelInterface $http_kernel, CloudSystemBaseInterface $cloud_base) {
    $this->configFactory = $config_factory;
    $this->httpKernel = $http_kernel;
    $this->base = $cloud_base;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // A very high priority so that it can take precedent over anything else,
    // and thus be fast.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['json'];
  }

  /**
   * Handles any exception as a generic error page for JSON.
   *
   * @todo This should probably check the error reporting level.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  protected function onJson(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $error = Error::decodeException($exception);
    $code = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
    $request = $event->getRequest();
    $data = [
      'error' => $event->getException()->getMessage(),
    ];
    if ($code >= 500) {
      // Send mail.
      $error_message = sprintf('%s: %s in %s (line %s of %s)', $error['%type'], $error['@message'], $error['%function'], $error['%line'], $error['%file']);
      $errorBody = 'Database error: ' . PHP_EOL . $request->getUri()
        . PHP_EOL . "请求机器：" . $request->getHost()
        . PHP_EOL . '<b>Request:</b> '
        . PHP_EOL . $request->__toString()
        . PHP_EOL . PHP_EOL . PHP_EOL . '<b>Error Info: </b>'
        . PHP_EOL . $error_message;
      $mail = \Drupal::config('cloud_system.email_config')->get('default_mail');
      $this->base->sendMail($mail, [
        'subject' => 'Database Error',
        'body'    => $errorBody,
      ]);

      $data = [
        'error' => CloudSystemUtils::t('Internal Server Error.'),
      ];
      $code = Response::HTTP_INTERNAL_SERVER_ERROR;
    }
    $response = new JsonResponse($data, $code);
    $event->setResponse($response);
  }

  /**
   * Handles an HttpExceptionInterface exception for unknown formats.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  protected function onFormatUnknown(GetResponseForExceptionEvent $event) {
    /** @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface|\Exception $exception */
    $exception = $event->getException();

    $response = new Response($exception->getMessage(), $exception->getStatusCode(), $exception->getHeaders());
    $event->setResponse($response);
  }

  /**
   * Handles errors for this subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $format = $this->getFormat($event->getRequest());
    $exception = $event->getException();

    $method = 'on' . $format;
    if (!method_exists($this, $method)) {
      if ($exception instanceof HttpExceptionInterface) {
        $this->onFormatUnknown($event);
      }
      else {
        $this->onJson($event);
      }
      return;
    }
    $this->$method($event);
  }

  /**
   * Gets the error-relevant format from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string
   *   The format as which to treat the exception.
   */
  protected function getFormat(Request $request) {
    $format = $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT, $request->getRequestFormat());

    // These are all JSON errors for our purposes. Any special handling for
    // them can/should happen in earlier listeners if desired.
    if (in_array($format, ['drupal_modal', 'drupal_dialog', 'drupal_ajax'])) {
      $format = 'json';
    }

    // Make an educated guess that any Accept header type that includes "json"
    // can probably handle a generic JSON response for errors. As above, for
    // any format this doesn't catch or that wants custom handling should
    // register its own exception listener.
    foreach ($request->getAcceptableContentTypes() as $mime) {
      if (strpos($mime, 'html') === FALSE && strpos($mime, 'json') !== FALSE) {
        $format = 'json';
      }
    }

    return $format;
  }


  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', 51];
    return $events;
  }

}
