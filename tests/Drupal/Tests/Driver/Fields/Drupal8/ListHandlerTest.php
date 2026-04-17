<?php

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Driver\Fields\Drupal8\ListFloatHandler;
use Drupal\Driver\Fields\Drupal8\ListIntegerHandler;
use Drupal\Driver\Fields\Drupal8\ListStringHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the List* field handlers (shared ListHandlerBase logic).
 */
class ListHandlerTest extends TestCase {

  /**
   * Tests that values matching allowed_values labels are mapped to keys.
   */
  public function testExpandMapsLabelsToKeys() {
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
  public function testExpandReturnsOriginalValuesWhenNoMatch() {
    $handler = $this->createHandler(ListStringHandler::class, [
      'a' => 'Alpha',
    ]);

    $this->assertSame(['Unknown'], $handler->expand(['Unknown']));
  }

  /**
   * Tests that integer list values are mapped to keys.
   */
  public function testIntegerListMapsLabelsToKeys() {
    $handler = $this->createHandler(ListIntegerHandler::class, [
      1 => 'One',
      2 => 'Two',
    ]);

    $this->assertSame([2], $handler->expand(['Two']));
  }

  /**
   * Tests that float list values are mapped to keys.
   */
  public function testFloatListMapsLabelsToKeys() {
    $handler = $this->createHandler(ListFloatHandler::class, [
      '1.5' => 'One and a half',
    ]);

    $this->assertSame(['1.5'], $handler->expand(['One and a half']));
  }

  /**
   * Tests that a scalar value is cast to an array before lookup.
   */
  public function testExpandCastsScalarToArray() {
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
   * @param array $allowed_values
   *   The allowed_values map to inject via the fieldInfo setting.
   *
   * @return object
   *   The handler instance with fieldInfo populated.
   */
  protected function createHandler($class_name, array $allowed_values) {
    $field_info = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getSetting'])
      ->getMock();
    $field_info->method('getSetting')
      ->with('allowed_values')
      ->willReturn($allowed_values);

    $reflection = new \ReflectionClass($class_name);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty($class_name, 'fieldInfo');
    $property->setAccessible(TRUE);
    $property->setValue($handler, $field_info);

    return $handler;
  }

}
