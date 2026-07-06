<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for DateRecurHandler via the Core driver.
 *
 * The 'date_recur' field type (provided by drupal/date_recur) stores five
 * columns ('value', 'end_value', 'rrule', 'timezone', 'infinite'), which
 * DefaultHandler cannot marshal. The kernel test's value is proving the driver
 * resolves DateRecurHandler for type 'date_recur' and that the multi-column
 * storage accepts what the handler emits. The 'infinite' column is derived
 * from the rrule by the field type's preSave(), so it is left out of the
 * asserted round-trip.
 *
 * @group fields
 */
#[Group('fields')]
class DateRecurHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'datetime',
    'datetime_range',
    'date_recur',
  ];

  /**
   * Tests round-trip for a recurring date with an explicit timezone.
   */
  public function testDateRecurRoundTrip(): void {
    $this->attachField('field_schedule', 'date_recur');

    // date_recur stores 'value'/'end_value' verbatim in the record's own
    // 'timezone' (not UTC), so the handler relays the storage-format strings
    // through unchanged.
    $this->assertFieldRoundTripViaDriver('field_schedule', [
      [
        'value' => '2025-01-01T09:00:00',
        'end_value' => '2025-01-01T17:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=5',
        'timezone' => 'Australia/Sydney',
      ],
    ]);
  }

}
