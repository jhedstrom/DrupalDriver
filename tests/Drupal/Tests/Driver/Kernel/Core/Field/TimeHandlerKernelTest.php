<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

/**
 * Kernel round-trip test for TimeHandler via the Core driver.
 *
 * TimeHandler accepts a numeric number of seconds past midnight or a
 * parseable time string (e.g. "9:30 AM") and emits the storage integer.
 * The 'time' field type is provided by drupal/time_field.
 */
class TimeHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'time_field',
  ];

  /**
   * Tests round-trip for a time field with a numeric seconds value.
   */
  public function testTimeNumericRoundTrip(): void {
    $this->attachField('field_start', 'time');

    // 9:30 AM = 9 * 3600 + 30 * 60 = 34200 seconds past midnight.
    $this->assertFieldRoundTripViaDriver('field_start', [34200]);
  }

}
