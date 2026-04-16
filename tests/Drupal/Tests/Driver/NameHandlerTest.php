<?php

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
  public function testExpand(array $input, array $expected) {
    $handler = $this->createHandler();
    $result = $handler->expand($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpand().
   */
  public function dataProviderExpand() {
    return [
      'string shorthand family, given' => [
        ['Doe, John'],
        [['family' => 'Doe', 'given' => 'John']],
      ],
      'string shorthand family only' => [
        ['Doe'],
        [['family' => 'Doe', 'given' => NULL]],
      ],
      'named keys' => [
        [['given' => 'John', 'family' => 'Doe', 'middle' => 'Q']],
        [['given' => 'John', 'family' => 'Doe', 'middle' => 'Q']],
      ],
      'numeric indices' => [
        [['Dr', 'John', 'Quincy', 'Doe']],
        [['title' => 'Dr', 'given' => 'John', 'middle' => 'Quincy', 'family' => 'Doe']],
      ],
      'multiple values' => [
        [
          'Doe, John',
          ['given' => 'Jane', 'family' => 'Smith'],
        ],
        [
          ['family' => 'Doe', 'given' => 'John'],
          ['given' => 'Jane', 'family' => 'Smith'],
        ],
      ],
    ];
  }

  /**
   * Creates a NameHandler instance that bypasses the parent constructor.
   *
   * @return \Drupal\Driver\Fields\Drupal8\NameHandler
   *   The handler instance.
   */
  protected function createHandler() {
    $reflection = new \ReflectionClass(NameHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

}
