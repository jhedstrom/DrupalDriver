<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

/**
 * Kernel round-trip test for DaterangeHandler via the Core driver.
 *
 * DaterangeHandler extends DatetimeHandler and emits a multi-property payload
 * ({value, end_value}). The kernel test's value is proving the driver
 * resolves DaterangeHandler for type 'daterange' and that the dual-column
 * storage accepts what the handler emits.
 */
class DaterangeHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'datetime',
    'datetime_range',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // See DatetimeHandlerKernelTest - handler reads system.date:timezone.default
    // unconditionally; pinning UTC keeps the round-trip deterministic.
    $this->config('system.date')->set('timezone.default', 'UTC')->save();
  }

  /**
   * Tests round-trip for a daterange field with start and end datetimes.
   */
  public function testDaterangeRoundTrip(): void {
    $this->attachField('field_event_window', 'daterange', [
      'datetime_type' => 'datetime',
    ]);

    $this->assertFieldRoundTripViaDriver('field_event_window', [
      [
        'value' => '2026-07-15T09:00:00',
        'end_value' => '2026-07-15T17:00:00',
      ],
    ]);
  }

}
