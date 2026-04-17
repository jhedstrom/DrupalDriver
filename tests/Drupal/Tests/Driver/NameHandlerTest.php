<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver;

use Drupal\Driver\Fields\Drupal8\NameHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the NameHandler field handler.
 */
class NameHandlerTest extends TestCase {

  /**
   * Tests name field expansion.
   *
   * @param array $input
   *   The input values to expand.
   * @param array $expected
   *   The expected expanded values.
   *
   * @dataProvider dataProviderExpand
   */
  public function testExpand(array $input, array $expected): void {
    $handler = $this->createHandler();
    $result = $handler->expand($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpand().
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'string shorthand family, given' => [
        ['Doe, John'],
        [['family' => 'Doe', 'given' => 'John']],
    ];
    yield 'string shorthand family only' => [
        ['Doe'],
        [['family' => 'Doe', 'given' => NULL]],
    ];
    yield 'named keys' => [
        [['given' => 'John', 'family' => 'Doe', 'middle' => 'Q']],
        [['given' => 'John', 'family' => 'Doe', 'middle' => 'Q']],
    ];
    yield 'numeric indices' => [
        [['Dr', 'John', 'Quincy', 'Doe']],
        [['title' => 'Dr', 'given' => 'John', 'middle' => 'Quincy', 'family' => 'Doe']],
    ];
    yield 'multiple values' => [
        [
          'Doe, John',
          ['given' => 'Jane', 'family' => 'Smith'],
        ],
        [
          ['family' => 'Doe', 'given' => 'John'],
          ['given' => 'Jane', 'family' => 'Smith'],
        ],
    ];
  }

  /**
   * Creates a NameHandler instance that bypasses the parent constructor.
   *
   * @return \Drupal\Driver\Fields\Drupal8\NameHandler
   *   The handler instance.
   */
  protected function createHandler(): NameHandler {
    $reflection = new \ReflectionClass(NameHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

}
