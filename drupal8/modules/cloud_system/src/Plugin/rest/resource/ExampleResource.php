<?php

namespace Drupal\cloud_system\Plugin\rest\resource;

use Drupal\cloud_system\Plugin\CloudResourceBase;
use Drupal\Component\Utility\Html;
use Drupal\rest\ResourceResponse;

/**
 * Represents example as resources.
 *
 * @RestResource(
 *   id = "example",
 *   label = @Translation("example Resource"),
 *   serialization_class = "Array",
 *   uri_paths = {
 *     "canonical" = "/example/{id}",
 *     "https://www.drupal.org/link-relations/create" = "/example"
 *   }
 * )
 */
class ExampleResource extends CloudResourceBase {

  /**
   * Responds to GET requests.
   *
   * @param int $id
   *   资源ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   */
  public function get($id, $data, $request) {
    $return = [
      'code' => 1,
      'message' => '这是get方法',
      'id' => $id,
      'data' => $data,
      'param' => $this->base->request->get('token'),
      // 获取IP地址示例.
      'realIp' => $this->base->realIp(),
      // 请求接口示例.
      'httpclient' => $this->base->httpRequest('http://www.baidu.com', ['method' => 'GET']),
      // 数据库操作示例.
      'database' => $this->base->database->select('users', 'u')->fields('u')->execute()->fetchAll(),
    ];
    $response = new ResourceResponse($return, 200);
    return $response;
  }

  /**
   * Responds to POST requests.
   *
   * @param array $data
   *   Parameter array.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   */
  public function post($data) {
    $escapetest = isset($data['html']) ? Html::escape($data['html']) : '';
    $return = [
      'code' => 1,
      'message' => '这是post方法',
      'data' => $data,
      'html' => $escapetest,
    ];
    $response = new ResourceResponse($return, 200);
    return $response;
  }

  /**
   * Responds to PUT requests.
   *
   * @param int $id
   *   资源ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   */
  public function put($id) {
    $return = [
      'code' => 1,
      'message' => '这是put方法',
      'id' => $id,
    ];
    $response = new ResourceResponse($return, 200);
    return $response;
  }

  /**
   * Responds to DELETE requests.
   *
   * @param int $id
   *   资源ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   */
  public function delete($id) {
    $return = [
      'code' => 1,
      'message' => '这是delete方法',
      'id' => $id,
    ];
    $response = new ResourceResponse($return, 200);
    return $response;
  }

}
