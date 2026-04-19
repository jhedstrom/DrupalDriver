<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\ListFloatHandler;
use Drupal\Driver\Core\Field\ListIntegerHandler;
use Drupal\Driver\Core\Field\ListStringHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the List* field handlers (shared ListHandlerBase logic).
 */
#[Group('fields')]
class ListHandlerTest extends TestCase {

  /**
   * Tests that values matching allowed_values labels are mapped to keys.
   */
  public function testExpandMapsLabelsToKeys(): void {
    $handler = $this->createHandler(ListStringHandler::class, [
      'red' => 'Red',
      'green' => 'Green',
      'blue' => 'Blue',
    ]);

    $this->assertSame(['green', 'blue'], $handler->expand(['Green', 'Blue']));
  }

  /**
   * Tests that unmatched values fall through unchanged.
   */
  public function testExpandReturnsOriginalValuesWhenNoMatch(): void {
    $handler = $this->createHandler(ListStringHandler::class, [
      'a' => 'Alpha',
    ]);

    $this->assertSame(['Unknown'], $handler->expand(['Unknown']));
  }

  /**
   * Tests that integer list values are mapped to keys.
   */
  public function testIntegerListMapsLabelsToKeys(): void {
    $handler = $this->createHandler(ListIntegerHandler::class, [
      1 => 'One',
      2 => 'Two',
    ]);

    $this->assertSame([2], $handler->expand(['Two']));
  }

  /**
   * Tests that float list values are mapped to keys.
   */
  public function testFloatListMapsLabelsToKeys(): void {
    $handler = $this->createHandler(ListFloatHandler::class, [
      '1.5' => 'One and a half',
    ]);

    $this->assertSame(['1.5'], $handler->expand(['One and a half']));
  }

  /**
   * Tests that a scalar value is cast to an array before lookup.
   */
  public function testExpandCastsScalarToArray(): void {
    $handler = $this->createHandler(ListStringHandler::class, [
      'k' => 'Label',
    ]);

    $this->assertSame(['k'], $handler->expand('Label'));
  }

  /**
   * Creates a list handler with an injected field storage definition.
   *
   * @param string $class_name
   *   The handler class to instantiate.
   * @param array<string, string> $allowed_values
   *   The allowed_values map to inject via the fieldInfo setting.
   *
   * @return object
   *   The handler instance with fieldInfo populated.
   */
  protected function createHandler(string $class_name, array $allowed_values): object {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getSetting')
      ->with('allowed_values')
      ->willReturn($allowed_values);

    $reflection = new \ReflectionClass($class_name);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty($class_name, 'fieldInfo');
    $property->setValue($handler, $field_info);

    return $handler;
  }

}
