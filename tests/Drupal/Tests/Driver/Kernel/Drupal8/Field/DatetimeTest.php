<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;

/**
 * Tests the driver's handling of datetime fields.
 *
 * @todo add test for date-only field.
 *
 * @group driver
 */
class DatetimeTest extends DriverFieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'field', 'datetime'];

  /**
   * Machine name of the field type being tested.
   *
   * @string
   */
  protected $fieldType = 'datetime';

  /**
   * Test an absolute value for a datetime field.
   */
  public function testDatetimeAbsolute() {
    $field = ['2015-02-10 17:45:00'];
    $fieldExpected = ['2015-02-10T17:45:00'];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test a relative value in a datetime field.
   */
  public function testDatetimeRelative() {
    $field = ['relative: 2015-02-10 17:45:00 + 1 day'];
    $fieldExpected = ['2015-02-11T06:45:00'];
    $this->assertCreatedWithField($fieldExpected);
  }

}
