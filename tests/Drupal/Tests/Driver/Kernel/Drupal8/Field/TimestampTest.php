<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

/**
 * Tests the driver's handling of timestamp fields.
 *
 * @group driver
 */
class TimestampTest extends DriverFieldKernelTestBase {

  /**
   * Machine name of the field type being tested.
   *
   * @var string
   */
  protected $fieldType = 'timestamp';

  /**
   * Test a morning value in 12hr clock for a timestamp field.
   */
  public function testTimestampPm() {
    $field = ['07/27/2014 12:03pm UTC'];
    $fieldExpected = ['1406462580'];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test a morning value in 12hr clock for a timestamp field.
   */
  public function testTimestampAm() {
    $field = ['04/27/2013 11:11am UTC'];
    $fieldExpected = ['1367061060'];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

}
