<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\TextWithSummaryHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TextWithSummaryHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class TextWithSummaryHandlerTest extends TestCase {

  /**
   * Tests that expand() returns a canonical list of records.
   */
  public function testExpandReturnsCanonicalRecordList(): void {
    $handler = $this->createHandler();

    $values = [
      ['value' => 'body text', 'summary' => 'short'],
    ];

    $this->assertSame($values, $handler->expand($values));
  }

  /**
   * Creates a TextWithSummaryHandler with the main property injected.
   */
  protected function createHandler(): TextWithSummaryHandler {
    $reflection = new \ReflectionClass(TextWithSummaryHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $property->setValue($handler, 'value');

    return $handler;
  }

}
