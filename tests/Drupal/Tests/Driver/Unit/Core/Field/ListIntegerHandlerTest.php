<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\ListIntegerHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the ListIntegerHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class ListIntegerHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getSetting')
      ->with('allowed_values')
      ->willReturn([
        1 => 'One',
        2 => 'Two',
      ]);

    $reflection = new \ReflectionClass(ListIntegerHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $info_property = new \ReflectionProperty(ListIntegerHandler::class, 'fieldInfo');
    $info_property->setValue($handler, $field_info);

    $main_property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_property->setValue($handler, 'value');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'label resolves to integer key' => [
      ['Two'],
      [2],
      NULL,
      NULL,
    ];
    yield 'unmatched value passes through' => [
      ['Unknown'],
      ['Unknown'],
      NULL,
      NULL,
    ];

    yield 'record missing main property rejected' => [
      ['unexpected' => 'oops'],
      NULL,
      \InvalidArgumentException::class,
      'Field record must include the main property "value"',
    ];
  }

}
