<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\DateRecurHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the DateRecurHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class DateRecurHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $reflection = new \ReflectionClass(DateRecurHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $property->setValue($handler, 'value');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'bare scalar maps to value column' => [
      '2025-01-01T09:00:00',
      [['value' => '2025-01-01T09:00:00']],
      NULL,
      NULL,
    ];
    yield 'list of scalars is multiple value deltas' => [
      ['2025-01-01T09:00:00', '2025-06-15T14:30:00'],
      [['value' => '2025-01-01T09:00:00'], ['value' => '2025-06-15T14:30:00']],
      NULL,
      NULL,
    ];
    yield 'full record with all five columns relays unchanged' => [
      [
        'value' => '2025-01-01T09:00:00',
        'end_value' => '2025-01-01T17:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=5',
        'timezone' => 'Australia/Sydney',
        'infinite' => 0,
      ],
      [
        [
          'value' => '2025-01-01T09:00:00',
          'end_value' => '2025-01-01T17:00:00',
          'rrule' => 'FREQ=DAILY;COUNT=5',
          'timezone' => 'Australia/Sydney',
          'infinite' => 0,
        ],
      ],
      NULL,
      NULL,
    ];
    yield 'record without rrule relays unchanged' => [
      [
        'value' => '2025-01-01T09:00:00',
        'end_value' => '2025-01-01T17:00:00',
        'timezone' => 'UTC',
      ],
      [
        [
          'value' => '2025-01-01T09:00:00',
          'end_value' => '2025-01-01T17:00:00',
          'timezone' => 'UTC',
        ],
      ],
      NULL,
      NULL,
    ];
    yield 'list of records relays unchanged' => [
      [
        ['value' => '2025-01-01T09:00:00', 'timezone' => 'UTC'],
        ['value' => '2025-06-15T14:30:00', 'timezone' => 'Europe/London'],
      ],
      [
        ['value' => '2025-01-01T09:00:00', 'timezone' => 'UTC'],
        ['value' => '2025-06-15T14:30:00', 'timezone' => 'Europe/London'],
      ],
      NULL,
      NULL,
    ];

    yield 'mixed positional and named keys rejected' => [
      ['2025-01-01T09:00:00', 'timezone' => 'UTC'],
      NULL,
      \InvalidArgumentException::class,
      'Field value cannot mix positional and named keys',
    ];
    yield 'record missing main property rejected' => [
      ['timezone' => 'UTC', 'rrule' => 'FREQ=DAILY;COUNT=5'],
      NULL,
      \InvalidArgumentException::class,
      'Field record must include the main property "value"',
    ];
  }

}
