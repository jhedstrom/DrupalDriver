<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for datetime fields via the Core driver.
 *
 * Unit tests already cover DatetimeHandler::expand() math. This test adds
 * the integration proof: Core::entityCreate resolves DatetimeHandler through
 * its lookup chain, the handler's output is accepted by real datetime field
 * storage, and the stored value round-trips unchanged.
 *
 * @group fields
 */
#[Group('fields')]
class DatetimeHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'datetime',
  ];

  /**
   * Tests round-trip for a datetime field (with time component).
   */
  public function testDatetimeRoundTrip(): void {
    $this->attachField('field_event_date', 'datetime', [
      'datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME,
    ]);
    $this->assertFieldRoundTripViaDriver('field_event_date', ['2026-07-15T10:00:00']);
  }

  /**
   * Tests round-trip for a date-only field.
   */
  public function testDateOnlyRoundTrip(): void {
    $this->attachField('field_birthday', 'datetime', [
      'datetime_type' => DateTimeItem::DATETIME_TYPE_DATE,
    ]);
    $this->assertFieldRoundTripViaDriver('field_birthday', ['2026-07-15']);
  }

}
