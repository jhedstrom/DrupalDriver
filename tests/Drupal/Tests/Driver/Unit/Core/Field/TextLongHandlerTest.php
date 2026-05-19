<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\TextLongHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TextLongHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class TextLongHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $reflection = new \ReflectionClass(TextLongHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $property->setValue($handler, 'value');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'bare scalar' => [
      'Body copy.',
      [['value' => 'Body copy.']],
      NULL,
      NULL,
    ];
    yield 'single record with value and format' => [
      ['value' => 'Body copy.', 'format' => 'plain_text'],
      [['value' => 'Body copy.', 'format' => 'plain_text']],
      NULL,
      NULL,
    ];

    yield 'mixed positional and named keys rejected' => [
      ['Body.', 'format' => 'plain_text'],
      NULL,
      \InvalidArgumentException::class,
      'Field value cannot mix positional and named keys',
    ];
    yield 'record missing main property rejected' => [
      ['format' => 'plain_text'],
      NULL,
      \InvalidArgumentException::class,
      'Field record must include the main property "value"',
    ];
  }

}
