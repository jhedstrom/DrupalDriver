<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\TimeHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the TimeHandler field handler.
 */
#[Group('fields')]
class TimeHandlerTest extends TestCase {

  /**
 * Tests time field expansion.
 *
 * @param array<int, mixed> $input
 *   The input values to expand.
 * @param array<int, mixed> $expected
 *   The expected expanded values.
 */
  #[DataProvider('dataProviderExpand')]
  public function testExpand(array $input, array $expected): void {
    $handler = $this->createHandler();
    $result = $handler->expand($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpand().
   */
  public static function dataProviderExpand(): \Iterator {
    // Seconds past midnight for known times.
    // 9:30 AM = 9*3600 + 30*60 = 34200.
    // 2:15:30 PM = 14*3600 + 15*60 + 30 = 51330.
    // Midnight = 0.
    $midnight = strtotime('today midnight');
    yield 'numeric integer passthrough' => [
      [34200],
      [34200],
    ];
    yield 'numeric string passthrough' => [
      ['34200'],
      ['34200'],
    ];
    yield 'time string 9:30 AM' => [
      ['9:30 AM'],
      [strtotime('9:30 AM') - $midnight],
    ];
    yield 'time string 14:15:30' => [
      ['14:15:30'],
      [strtotime('14:15:30') - $midnight],
    ];
    yield 'time string midnight' => [
      ['midnight'],
      [0],
    ];
    yield 'multiple mixed values' => [
      [3600, '9:30 AM', '0'],
      [3600, strtotime('9:30 AM') - $midnight, '0'],
    ];
  }

  /**
   * Creates a TimeHandler instance that bypasses the parent constructor.
   *
   * @return \Drupal\Driver\Core\Field\TimeHandler
   *   The handler instance.
   */
  protected function createHandler(): TimeHandler {
    // Use reflection to bypass AbstractHandler constructor which requires
    // a full Drupal bootstrap.
    $reflection = new \ReflectionClass(TimeHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

}
