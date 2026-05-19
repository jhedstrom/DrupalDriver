<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\SmartdateHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the SmartdateHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class SmartdateHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    return (new \ReflectionClass(SmartdateHandler::class))->newInstanceWithoutConstructor();
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    // 2026-07-15T09:00:00 UTC = 1784106000.
    // 2026-07-15T17:00:00 UTC = 1784134800. Duration: 480 minutes.
    yield 'empty array returns empty list' => [
      [],
      [],
      NULL,
      NULL,
    ];
    yield 'non-array returns empty list' => [
      'not-an-array',
      [],
      NULL,
      NULL,
    ];
    yield 'single positional pair' => [
      [1784106000, 1784134800],
      [[
        'value' => 1784106000,
        'end_value' => 1784134800,
        'duration' => 480,
        'rrule' => NULL,
        'rrule_index' => NULL,
        'timezone' => '',
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'single keyed record' => [
      ['value' => 1784106000, 'end_value' => 1784134800],
      [[
        'value' => 1784106000,
        'end_value' => 1784134800,
        'duration' => 480,
        'rrule' => NULL,
        'rrule_index' => NULL,
        'timezone' => '',
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'list of keyed records' => [
      [
        ['value' => 1784106000, 'end_value' => 1784134800],
        ['value' => 1784790000, 'end_value' => 1784818800],
      ],
      [
        [
          'value' => 1784106000,
          'end_value' => 1784134800,
          'duration' => 480,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
        [
          'value' => 1784790000,
          'end_value' => 1784818800,
          'duration' => 480,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
      NULL,
      NULL,
    ];
    yield 'explicit duration overrides derived' => [
      ['value' => 1784106000, 'end_value' => 1784134800, 'duration' => 999],
      [[
        'value' => 1784106000,
        'end_value' => 1784134800,
        'duration' => 999,
        'rrule' => NULL,
        'rrule_index' => NULL,
        'timezone' => '',
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'NULL end yields zero duration' => [
      ['value' => 1784106000],
      [[
        'value' => 1784106000,
        'end_value' => NULL,
        'duration' => 0,
        'rrule' => NULL,
        'rrule_index' => NULL,
        'timezone' => '',
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'NULL endpoints preserved' => [
      ['value' => NULL, 'end_value' => NULL],
      [[
        'value' => NULL,
        'end_value' => NULL,
        'duration' => 0,
        'rrule' => NULL,
        'rrule_index' => NULL,
        'timezone' => '',
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'end before start clamps duration to zero' => [
      ['value' => 1784134800, 'end_value' => 1784106000],
      [[
        'value' => 1784134800,
        'end_value' => 1784106000,
        'duration' => 0,
        'rrule' => NULL,
        'rrule_index' => NULL,
        'timezone' => '',
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'date string parsed via strtotime' => [
      ['value' => '2026-07-15T09:00:00 UTC', 'end_value' => '2026-07-15T17:00:00 UTC'],
      [[
        'value' => 1784106000,
        'end_value' => 1784134800,
        'duration' => 480,
        'rrule' => NULL,
        'rrule_index' => NULL,
        'timezone' => '',
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'rrule, rrule_index and timezone passed through' => [
      [
        'value' => 1784106000,
        'end_value' => 1784134800,
        'rrule' => 42,
        'rrule_index' => 3,
        'timezone' => 'Australia/Sydney',
      ],
      [[
        'value' => 1784106000,
        'end_value' => 1784134800,
        'duration' => 480,
        'rrule' => 42,
        'rrule_index' => 3,
        'timezone' => 'Australia/Sydney',
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'unparseable string becomes NULL' => [
      ['value' => 'not a date', 'end_value' => NULL],
      [[
        'value' => NULL,
        'end_value' => NULL,
        'duration' => 0,
        'rrule' => NULL,
        'rrule_index' => NULL,
        'timezone' => '',
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'numeric string timestamp cast to int' => [
      ['value' => '1784106000', 'end_value' => '1784134800'],
      [[
        'value' => 1784106000,
        'end_value' => 1784134800,
        'duration' => 480,
        'rrule' => NULL,
        'rrule_index' => NULL,
        'timezone' => '',
      ],
      ],
      NULL,
      NULL,
    ];

    yield 'non-array delta in list rejected' => [
      [
        ['value' => 1784106000, 'end_value' => 1784134800],
        'not-a-record',
      ],
      NULL,
      \InvalidArgumentException::class,
      'Smartdate field delta must be an array',
    ];
  }

}
