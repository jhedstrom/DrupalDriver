<?php

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Driver\Fields\Drupal8\DefaultHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DefaultHandler field handler.
 */
class DefaultHandlerTest extends TestCase {

  /**
   * Tests that expand() returns the input unchanged.
   */
  public function testExpandReturnsValuesUnchanged() {
    $reflection = new \ReflectionClass(DefaultHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $values = ['one', 'two', 3];

    $this->assertSame($values, $handler->expand($values));
  }

}
