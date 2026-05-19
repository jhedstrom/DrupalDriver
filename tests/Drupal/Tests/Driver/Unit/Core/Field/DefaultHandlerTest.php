<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
    return $this->handlerWithColumns(['value' => []]);
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
   * Tests that a multi-column field triggers the loud-failure policy.
   */
  public function testExpandThrowsForMultipleColumns(): void {
    $handler = $this->handlerWithColumns(['value' => [], 'format' => []]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No dedicated handler is registered');
    $this->expectExceptionMessage('2 column(s) (value, format)');

    $handler->expand([['value' => 'hello']]);
  }

  /**
   * Tests that a single-column field not keyed by 'value' triggers failure.
   */
  public function testExpandThrowsForSingleColumnNotNamedValue(): void {
    $handler = $this->handlerWithColumns(['target_id' => []]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('target_id');

    $handler->expand([['value' => 42]]);
  }

  /**
   * Builds a DefaultHandler wired to a mocked field storage/config pair.
   *
   * @param array<string, array<string, mixed>> $columns
   *   Column descriptors keyed by column name.
   */
  protected function handlerWithColumns(array $columns): DefaultHandler {
    $storage = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage->method('getColumns')->willReturn($columns);
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

    $main_property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_property->setValue($handler, 'value');

    return $handler;
  }

}
