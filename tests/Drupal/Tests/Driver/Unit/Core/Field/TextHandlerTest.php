<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\TextHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TextHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class TextHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $reflection = new \ReflectionClass(TextHandler::class);
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
      'Inline text.',
      [['value' => 'Inline text.']],
      NULL,
      NULL,
    ];
    yield 'list of scalars' => [
      ['a', 'b'],
      [['value' => 'a'], ['value' => 'b']],
      NULL,
      NULL,
    ];
    yield 'single record with value and format' => [
      ['value' => 'Inline text.', 'format' => 'plain_text'],
      [['value' => 'Inline text.', 'format' => 'plain_text']],
      NULL,
      NULL,
    ];
    yield 'list of records' => [
      [['value' => 'a', 'format' => 'plain_text'], ['value' => 'b']],
      [['value' => 'a', 'format' => 'plain_text'], ['value' => 'b']],
      NULL,
      NULL,
    ];

    yield 'mixed positional and named keys rejected' => [
      ['a', 'format' => 'plain_text'],
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
