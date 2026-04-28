<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\DefaultHandler;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DefaultHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class DefaultHandlerTest extends TestCase {

  /**
   * Tests that a single 'value' column passes through unchanged.
   */
  public function testExpandReturnsValuesForSingleValueColumn(): void {
    $handler = $this->handlerWithColumns(['value' => []]);

    $this->assertSame(['one', 'two'], $handler->expand(['one', 'two']));
  }

  /**
   * Tests that a multi-column field triggers the loud-failure policy.
   */
  public function testExpandThrowsForMultipleColumns(): void {
    $handler = $this->handlerWithColumns(['value' => [], 'format' => []]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No dedicated handler is registered');
    $this->expectExceptionMessage('2 column(s) (value, format)');

    $handler->expand('hello');
  }

  /**
   * Tests that a single-column field not keyed by 'value' triggers failure.
   */
  public function testExpandThrowsForSingleColumnNotNamedValue(): void {
    $handler = $this->handlerWithColumns(['target_id' => []]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('target_id');

    $handler->expand(42);
  }

  /**
   * Builds a DefaultHandler wired to a mocked field storage/config pair.
   *
   * @param array<string, array<string, mixed>> $columns
   *   Column descriptors keyed by column name (the content is irrelevant;
   *   only the array keys are inspected by DefaultHandler).
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

    return $handler;
  }

}
