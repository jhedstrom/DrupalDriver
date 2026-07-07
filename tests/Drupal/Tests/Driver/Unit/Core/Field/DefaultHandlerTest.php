<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\DefaultHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the DefaultHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class DefaultHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    return $this->handlerWithProperties(['value' => DataDefinition::create('string')]);
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'bare scalar' => [
      'hello',
      [['value' => 'hello']],
      NULL,
      NULL,
    ];
    yield 'list of scalars' => [
      ['one', 'two'],
      [['value' => 'one'], ['value' => 'two']],
      NULL,
      NULL,
    ];
    yield 'records pass through unchanged' => [
      [['value' => 'one'], ['value' => 'two']],
      [['value' => 'one'], ['value' => 'two']],
      NULL,
      NULL,
    ];
    yield 'integer scalar' => [
      42,
      [['value' => 42]],
      NULL,
      NULL,
    ];

    yield 'mixed positional and named keys rejected' => [
      ['hello', 'extra' => 'unexpected'],
      NULL,
      \InvalidArgumentException::class,
      'Field value cannot mix positional and named keys',
    ];
    yield 'record missing main property rejected' => [
      ['unexpected' => 'oops'],
      NULL,
      \InvalidArgumentException::class,
      'Field record must include the main property "value"',
    ];
  }

  /**
   * Tests that a multi-column field of plain scalars relays records verbatim.
   */
  public function testExpandPassesMultiColumnScalars(): void {
    $handler = $this->handlerWithProperties([
      'color' => DataDefinition::create('string'),
      'opacity' => DataDefinition::create('float'),
    ], 'color');

    $records = [['color' => '#1A2B3C', 'opacity' => 0.5]];

    $this->assertSame($records, $handler->expand($records));
  }

  /**
   * Tests that a computed reference property does not block the pass-through.
   */
  public function testExpandIgnoresComputedProperties(): void {
    $handler = $this->handlerWithProperties([
      'value' => DataDefinition::create('string'),
      'entity' => DataReferenceTargetDefinition::create('integer')->setComputed(TRUE),
    ]);

    $this->assertSame([['value' => 'hello']], $handler->expand('hello'));
  }

  /**
   * Tests that an entity-reference target triggers the loud-failure policy.
   */
  public function testExpandThrowsForEntityReferenceTarget(): void {
    $handler = $this->handlerWithProperties([
      'target_id' => DataReferenceTargetDefinition::create('integer'),
    ], 'target_id');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No dedicated handler is registered');
    $this->expectExceptionMessage('property "target_id" is an entity-reference target');

    $handler->expand([['target_id' => 42]]);
  }

  /**
   * Tests that a datetime property triggers the loud-failure policy.
   */
  public function testExpandThrowsForDatetimeProperty(): void {
    $handler = $this->handlerWithProperties([
      'value' => DataDefinition::create('datetime_iso8601'),
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('property "value" is a "datetime_iso8601" value');

    $handler->expand('2025-01-01');
  }

  /**
   * Tests that a complex property triggers the loud-failure policy.
   */
  public function testExpandThrowsForComplexProperty(): void {
    $handler = $this->handlerWithProperties([
      'value' => DataDefinition::create('string'),
      'options' => MapDataDefinition::create(),
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('property "options" holds a complex or nested value');

    $handler->expand('hello');
  }

  /**
   * Builds a DefaultHandler wired to a mocked storage/config pair.
   *
   * @param array<string, \Drupal\Core\TypedData\DataDefinitionInterface> $properties
   *   Property definitions keyed by property name.
   * @param string $main_property
   *   The field's main property name.
   */
  protected function handlerWithProperties(array $properties, string $main_property = 'value'): DefaultHandler {
    $storage = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage->method('getPropertyDefinitions')->willReturn($properties);
    $storage->method('getName')->willReturn('field_example');
    $storage->method('getType')->willReturn('example_type');
    $storage->method('getTargetEntityTypeId')->willReturn('node');

    $config = $this->createMock(FieldDefinitionInterface::class);
    $config->method('getTargetBundle')->willReturn('article');

    $reflection = new \ReflectionClass(DefaultHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $info_prop = $reflection->getParentClass()->getProperty('fieldInfo');
    $info_prop->setValue($handler, $storage);

    $config_prop = $reflection->getParentClass()->getProperty('fieldConfig');
    $config_prop->setValue($handler, $config);

    $main_prop = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_prop->setValue($handler, $main_property);

    return $handler;
  }

}
