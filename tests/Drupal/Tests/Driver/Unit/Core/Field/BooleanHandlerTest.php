<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\BooleanHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the BooleanHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class BooleanHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $field_config = $this->createMock(FieldDefinitionInterface::class);
    $field_config->method('getSettings')->willReturn([
      'on_label' => 'Published',
      'off_label' => 'Draft',
    ]);

    $reflection = new \ReflectionClass(BooleanHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $config_property = new \ReflectionProperty(BooleanHandler::class, 'fieldConfig');
    $config_property->setValue($handler, $field_config);

    $main_property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_property->setValue($handler, 'value');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'canonical yes resolves to 1' => ['Yes', [1], NULL, NULL];
    yield 'canonical no resolves to 0' => ['no', [0], NULL, NULL];
    yield 'field on_label resolves to 1' => ['Published', [1], NULL, NULL];
    yield 'field off_label resolves to 0' => ['Draft', [0], NULL, NULL];
    yield 'mixed list of labels and canonical' => [
      ['Published', 'no', 'true'],
      [1, 0, 1],
      NULL,
      NULL,
    ];
    yield 'list of canonical forms' => [
      ['1', '0', 'true', 'false', 'yes', 'no', 'on', 'off'],
      [1, 0, 1, 0, 1, 0, 1, 0],
      NULL,
      NULL,
    ];

    yield 'unrecognised value rejected' => [
      ['maybe'],
      NULL,
      \RuntimeException::class,
      'Cannot convert "maybe" to a boolean',
    ];
    yield 'mixed positional and named keys rejected' => [
      ['Yes', 'extra' => 'unexpected'],
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

}
