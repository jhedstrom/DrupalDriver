<?php

namespace Drupal\Tests\Driver;

use Drupal\Driver\Fields\Drupal8\TimeHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the TimeHandler field handler.
 */
class TimeHandlerTest extends TestCase {

  /**
   * Tests time field expansion.
   *
   * @param array $input
   *   The input values to expand.
   * @param array $expected
   *   The expected expanded values.
   *
   * @dataProvider dataProviderExpand
   */
  public function testExpand(array $input, array $expected) {
    $handler = $this->createHandler();
    $result = $handler->expand($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpand().
   */
  public function dataProviderExpand() {
    // Seconds past midnight for known times.
    // 9:30 AM = 9*3600 + 30*60 = 34200.
    // 2:15:30 PM = 14*3600 + 15*60 + 30 = 51330.
    // Midnight = 0.
    $midnight = strtotime('today midnight');

    return [
      'numeric integer passthrough' => [
        [34200],
        [34200],
      ],
      'numeric string passthrough' => [
        ['34200'],
        ['34200'],
      ],
      'time string 9:30 AM' => [
        ['9:30 AM'],
        [strtotime('9:30 AM') - $midnight],
      ],
      'time string 14:15:30' => [
        ['14:15:30'],
        [strtotime('14:15:30') - $midnight],
      ],
      'time string midnight' => [
        ['midnight'],
        [0],
      ],
      'multiple mixed values' => [
        [3600, '9:30 AM', '0'],
        [3600, strtotime('9:30 AM') - $midnight, '0'],
      ],
    ];
  }

  /**
   * Creates a TimeHandler instance that bypasses the parent constructor.
   *
   * @return \Drupal\Driver\Fields\Drupal8\TimeHandler
   *   The handler instance.
   */
  protected function createHandler() {
    // Use reflection to bypass AbstractHandler constructor which requires
    // a full Drupal bootstrap.
    $reflection = new \ReflectionClass(TimeHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

}
