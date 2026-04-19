<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Core\Field;

use Drupal\Driver\Core\Field\DefaultHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DefaultHandler field handler.
 */
class DefaultHandlerTest extends TestCase {

  /**
   * Tests that expand() returns the input unchanged.
   */
  public function testExpandReturnsValuesUnchanged(): void {
    $reflection = new \ReflectionClass(DefaultHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $values = ['one', 'two', 3];

    $this->assertSame($values, $handler->expand($values));
  }

}
