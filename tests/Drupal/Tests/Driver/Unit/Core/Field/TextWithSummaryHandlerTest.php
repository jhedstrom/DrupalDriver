<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
   * Creates a TextWithSummaryHandler with a fieldInfo stub for normalise().
   */
  protected function createHandler(): TextWithSummaryHandler {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getMainPropertyName')->willReturn('value');

    $reflection = new \ReflectionClass(TextWithSummaryHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(AbstractHandler::class, 'fieldInfo');
    $property->setValue($handler, $field_info);

    return $handler;
  }

}
