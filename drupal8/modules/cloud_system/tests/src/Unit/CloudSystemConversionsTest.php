<?php

namespace Drupal\Tests\cloud_system\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Demonstrates how to write tests.
 *
 * @group cloud_system
 */
class CloudSystemConversionsTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Convert Celsius to Fahrenheit.
   *
   * @param int $temp
   *   Temp.
   *
   * @return int
   *   Number.
   */
  public function celsiusToFahrenheit($temp) {
    return ($temp * (9 / 5)) + 32;
  }

  /**
   * Convert centimeter to inches.
   *
   * @param int $length
   *   Length.
   *
   * @return int
   *   Number.
   */
  public function centimeterToInch($length) {
    return $length / 2.54;
  }

  /**
   * A simple test that tests our celsiusToFahrenheit() function.
   */
  public function testOneConversion() {
    // Confirm that 0C = 32F.
    $this->assertEquals(31, $this->celsiusToFahrenheit(0));
  }

  /**
   * Provides data for the testCentimetersToInches method.
   *
   * @return array
   *   Array.
   */
  public function providerCentimetersToInches() {
    return [
      [2.545, 1],
      [254, 100],
      [0, 0],
      [-2.54, -1],
    ];
  }

  /**
   * Tests centimetersToInches method.
   *
   * @dataProvider providerCentimetersToInches
   */
  public function testCentimetersToInches($length, $expectedValue) {
    $this->assertEquals($expectedValue, $this->centimeterToInch($length));
  }

}
