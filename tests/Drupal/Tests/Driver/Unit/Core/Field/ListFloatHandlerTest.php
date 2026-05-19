<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\ListFloatHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the ListFloatHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class ListFloatHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getSetting')
      ->with('allowed_values')
      ->willReturn([
        '1.5' => 'One and a half',
        '2.5' => 'Two and a half',
      ]);

    $reflection = new \ReflectionClass(ListFloatHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $info_property = new \ReflectionProperty(ListFloatHandler::class, 'fieldInfo');
    $info_property->setValue($handler, $field_info);

    $main_property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_property->setValue($handler, 'value');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'label resolves to float key' => [
      ['One and a half'],
      ['1.5'],
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
