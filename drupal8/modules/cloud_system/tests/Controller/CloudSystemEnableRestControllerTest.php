<?php

/**
 * @file
 * Contains \Drupal\cloud_system\Tests\CloudSystemEnableRestController.
 */

namespace Drupal\cloud_system\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the cloud_system module.
 */
class CloudSystemEnableRestControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "cloud_system CloudSystemEnableRestController's controller functionality",
      'description' => 'Test Unit for module cloud_system and controller CloudSystemEnableRestController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests cloud_system functionality.
   */
  public function testCloudSystemEnableRestController() {
    // Check that the basic functions of module cloud_system.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
