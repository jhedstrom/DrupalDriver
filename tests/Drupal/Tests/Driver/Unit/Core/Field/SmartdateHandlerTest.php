<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\SmartdateHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the SmartdateHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class SmartdateHandlerTest extends TestCase {

  /**
   * Tests smartdate field expansion.
   *
   * @param mixed $input
   *   The input values to expand.
   * @param array<int, array<string, mixed>> $expected
   *   The expected expanded records.
   *
   * @dataProvider dataProviderExpand
   */
  #[DataProvider('dataProviderExpand')]
  public function testExpand(mixed $input, array $expected): void {
    $handler = $this->createHandler();
    $result = $handler->expand($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpand().
   */
  public static function dataProviderExpand(): \Iterator {
    // 2026-07-15T09:00:00 UTC = 1784106000.
    // 2026-07-15T17:00:00 UTC = 1784134800.
    // Duration: (1784134800 - 1784106000) / 60 = 480 minutes.
    yield 'empty array returns empty list' => [
      [],
      [],
    ];

    yield 'non-array input returns empty list' => [
      'not-an-array',
      [],
    ];

    yield 'single positional pair' => [
      [1784106000, 1784134800],
      [
        [
          'value' => 1784106000,
          'end_value' => 1784134800,
          'duration' => 480,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
    ];

    yield 'single named record' => [
      ['value' => 1784106000, 'end_value' => 1784134800],
      [
        [
          'value' => 1784106000,
          'end_value' => 1784134800,
          'duration' => 480,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
    ];

    yield 'list of named records (multi-delta)' => [
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
    ];

    yield 'explicit duration overrides auto-computed' => [
      ['value' => 1784106000, 'end_value' => 1784134800, 'duration' => 999],
      [
        [
          'value' => 1784106000,
          'end_value' => 1784134800,
          'duration' => 999,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
    ];

    yield 'duration defaults to zero when only start provided' => [
      ['value' => 1784106000],
      [
        [
          'value' => 1784106000,
          'end_value' => NULL,
          'duration' => 0,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
    ];

    yield 'NULL endpoints preserved' => [
      ['value' => NULL, 'end_value' => NULL],
      [
        [
          'value' => NULL,
          'end_value' => NULL,
          'duration' => 0,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
    ];

    yield 'end before start clamps duration to zero' => [
      ['value' => 1784134800, 'end_value' => 1784106000],
      [
        [
          'value' => 1784134800,
          'end_value' => 1784106000,
          'duration' => 0,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
    ];

    yield 'date string parsed via strtotime' => [
      ['value' => '2026-07-15T09:00:00 UTC', 'end_value' => '2026-07-15T17:00:00 UTC'],
      [
        [
          'value' => 1784106000,
          'end_value' => 1784134800,
          'duration' => 480,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
    ];

    yield 'rrule, rrule_index and timezone passed through' => [
      [
        'value' => 1784106000,
        'end_value' => 1784134800,
        'rrule' => 42,
        'rrule_index' => 3,
        'timezone' => 'Australia/Sydney',
      ],
      [
        [
          'value' => 1784106000,
          'end_value' => 1784134800,
          'duration' => 480,
          'rrule' => 42,
          'rrule_index' => 3,
          'timezone' => 'Australia/Sydney',
        ],
      ],
    ];

    yield 'unparseable string becomes NULL' => [
      ['value' => 'not a date', 'end_value' => NULL],
      [
        [
          'value' => NULL,
          'end_value' => NULL,
          'duration' => 0,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
    ];

    yield 'numeric string timestamp cast to int' => [
      ['value' => '1784106000', 'end_value' => '1784134800'],
      [
        [
          'value' => 1784106000,
          'end_value' => 1784134800,
          'duration' => 480,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => '',
        ],
      ],
    ];

    yield 'non-array record in list is skipped' => [
      [
        ['value' => 1784106000, 'end_value' => 1784134800],
        'not-a-record',
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
      ],
    ];
  }

  /**
   * Creates a SmartdateHandler instance that bypasses the parent constructor.
   */
  protected function createHandler(): SmartdateHandler {
    $reflection = new \ReflectionClass(SmartdateHandler::class);

    return $reflection->newInstanceWithoutConstructor();
  }

}
