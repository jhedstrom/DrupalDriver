<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;

/**
 * Tests the driver's handling of string fields.
 *
 * @group driver
 */
class StringTest extends DriverFieldKernelTestBase {

  /**
   * Machine name of the field type being tested.
   *
   * @string
   */
  protected $fieldType = 'string';

  /**
   * Test that an entity can be created with a single value in a string field.
   */
  public function testStringSingle() {
    $field = [$this->randomString()];
    $this->assertCreatedWithField($field);
  }

  /**
   * Test that an entity can be created with multiple values in a string field.
   */
  public function testStringMultiple() {
    $field = [$this->randomString(),$this->randomString()];
    $this->assertCreatedWithField($field);
  }

}
