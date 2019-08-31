<?php

namespace Drupal\cloud_system\Plugin;

use Drupal\cloud_system\CloudSystemBase;
use Drupal\cloud_system\CloudSystemDatabase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cloud_validator\InputValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\cloud_system\CloudSystemTrait;
use Drupal\Core\Routing\BcRoute;

/**
 * Rest webservice base class.
 */
class CloudResourceBase extends ResourceBase {
  use StringTranslationTrait;
  use CloudSystemTrait;

  /**
   * Endpoint.
   *
   * @var string
   */
  const END_POINT = '/API';

  /**
   * The CloudSystemBase object to supply basic functions.
   *
   * @var \Drupal\cloud_system\CloudSystemBase
   */
  protected $base;

  /**
   * @var \Drupal\cloud_validator\InputValidator
   */
  protected $validator;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @var \Drupal\cloud_system\CloudSystemDatabase
   */
  protected $vdb;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\cloud_system\CloudSystemBase $base
   *   The base object.
   * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
   *   The typed data validator service.
   * @param \Drupal\cloud_system\CloudSystemDatabase $vdb
   *   Provide some awesome function for db actions.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    CloudSystemBase $base,
    InputValidatorInterface $validator,
    CloudSystemDatabase $vdb) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->base = $base;
    $this->validator = $validator;
    $this->database = $this->base->database;
    $this->vdb = $vdb;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('cloud_system.base'),
      $container->get('cloud_validator.input_validator'),
      $container->get('cloud_system.database')
    );
  }

  /**
   * Implements ResourceInterface::permissions().
   *
   * Every plugin operation method gets its own user permission. Example:
   * "restful delete entity:node" with the title "Access DELETE on Node
   * resource".
   *
   * Note: disable view on the page /admin/people/permissions.
   */
  public function permissions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $collection = new RouteCollection();
    $definition = $this->getPluginDefinition();
    $canonical_path = isset($definition['uri_paths']['canonical']) ? $definition['uri_paths']['canonical'] : '/' . strtr($this->pluginId, ':', '/') . '/{id}';
    $create_path = isset($definition['uri_paths']['create']) ? $definition['uri_paths']['create'] : '/' . strtr($this->pluginId, ':', '/');
    $label = isset($definition['label']) ? $definition['label'] : '';

    // BC: the REST module originally created the POST URL for a resource by
    // reading the 'https://www.drupal.org/link-relations/create' URI path from
    // the plugin annotation. For consistency with entity type definitions, that
    // then changed to reading the 'create' URI path. For any REST Resource
    // plugins that were using the old mechanism, we continue to support that.
    if (!isset($definition['uri_paths']['create']) && isset($definition['uri_paths']['https://www.drupal.org/link-relations/create'])) {
      $create_path = $definition['uri_paths']['https://www.drupal.org/link-relations/create'];
    }
    $route_name = strtr($this->pluginId, ':', '.');
    $methods = $this
      ->availableMethods();
    foreach ($methods as $method) {
      $path = $method === 'POST' ? $create_path : $canonical_path;
      $path = self::END_POINT . $path;
      $route = $this->getBaseRouter($path, $method, $label);

      if (in_array($method, ['DELETE'], TRUE)) {
        $route->addRequirements(['_content_type_format' => implode('|', $this->serializerFormats)]);
      }

      // Note that '_format' and '_content_type_format' route requirements are
      // added in ResourceRoutes::getRoutesForResourceConfig().
      $collection
        ->add("{$route_name}.{$method}", $route);

      // BC: the REST module originally created per-format GET routes, instead
      // of a single route. To minimize the surface of this BC layer, this uses
      // route definitions that are as empty as possible, plus an outbound route
      // processor.
      // @see \Drupal\rest\RouteProcessor\RestResourceGetRouteProcessorBC
      if ($method === 'GET' || $method === 'HEAD') {
        foreach ($this->serializerFormats as $format_name) {
          $collection
            ->add("{$route_name}.{$method}.{$format_name}", (new BcRoute())
              ->setRequirement('_format', $format_name));
        }
      }
    }
    return $collection;
  }

  /**
   * Setups the base route for all HTTP methods.
   *
   * @param string $canonical_path
   *   The canonical path for the resource.
   * @param string $method
   *   The HTTP method to be used for the route.
   * @param string $label
   *   The translation string for the route.
   *
   * @return \Symfony\Component\Routing\Route
   *   The created base route.
   */
  protected function getBaseRouter($canonical_path, $method, $label) {
    //$lower_method = strtolower($method);

    $route = new Route($canonical_path, [
      '_controller' => 'Drupal\rest\RequestHandler::handle',
      // Pass the resource plugin ID along as default property.
      //'_plugin' => $this->pluginId,
      '_title' => $label->render(),
    ],
      [
        '_access' => 'TRUE',
        //'_permission' => 'View published content',
      ],
      [],
      '',
      [],
      // The HTTP method is a requirement for this route.
      [$method]
    );
    return $route;
  }

  /**
   * Format data for GET method.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function resourceGetResponse($data) {
    $code = $data['code'];
    $code = $code == 1 ? 200 : ($code == -1 ? 404 : 400);
    $response = new JsonResponse($data, $code);
    return $response;
  }

  /**
   * Prepare response data.
   */
  public function prepareResponseData($data, $fields = []) {
    $return = [];
    $responseData = isset($data['data']) ? $data['data'] : [];

    if (empty($responseData)) {
      return $data;
    }

    if (!empty($fields)) {
      foreach ($responseData as $response_data) {
        $temp = [];
        foreach ($fields as $field => $info) {
          if (isset($response_data[$field])) {
            $temp[$field] = $response_data[$field];
          }
        }

        $return[] = $temp;
      }

      if (empty($return)) {
        if (isset($data['limit'])) {
          unset($data['limit']);
        }

        if (isset($data['page'])) {
          unset($data['page']);
        }

        if (isset($data['total'])) {
          unset($data['total']);
        }

        if (isset($data['totalPage'])) {
          unset($data['totalPage']);
        }
      }

      $data['data'] = $return;
    }
    return $data;
  }

  /**
   * Get rest plugin id.
   */
  public function getRestPluginId($request) {
    return $request->attributes->get('_route');
  }

}
