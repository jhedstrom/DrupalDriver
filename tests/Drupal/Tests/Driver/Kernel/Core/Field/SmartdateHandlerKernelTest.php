<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for SmartdateHandler via the Core driver.
 *
 * SmartdateHandler emits a six-column payload ('value', 'end_value',
 * 'duration', 'rrule', 'rrule_index', 'timezone'). The kernel test's
 * value is proving the driver resolves SmartdateHandler for type
 * 'smartdate' and that the multi-column storage accepts what the handler
 * emits. The 'smartdate' field type is provided by drupal/smart_date.
 *
 * @group fields
 */
#[Group('fields')]
class SmartdateHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'datetime',
    'options',
    'smart_date',
  ];

  /**
   * Tests round-trip for a smartdate field with numeric Unix timestamps.
   */
  public function testSmartdateRoundTrip(): void {
    $this->attachField('field_event', 'smartdate');

    // 2026-07-15T09:00:00 UTC = 1784106000.
    // 2026-07-15T17:00:00 UTC = 1784134800.
    // Duration: (1784134800 - 1784106000) / 60 = 480 minutes.
    $this->assertFieldRoundTripViaDriver('field_event', [
      [
        'value' => 1784106000,
        'end_value' => 1784134800,
        'duration' => 480,
        'timezone' => 'UTC',
      ],
    ]);
  }

}
