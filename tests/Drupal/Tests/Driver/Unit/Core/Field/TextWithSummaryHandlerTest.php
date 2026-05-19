<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\TextWithSummaryHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TextWithSummaryHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class TextWithSummaryHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $reflection = new \ReflectionClass(TextWithSummaryHandler::class);
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
      'body text',
      [['value' => 'body text']],
      NULL,
      NULL,
    ];
    yield 'single record with summary' => [
      ['value' => 'body text', 'summary' => 'short'],
      [['value' => 'body text', 'summary' => 'short']],
      NULL,
      NULL,
    ];

    yield 'mixed positional and named keys rejected' => [
      ['body text', 'summary' => 'short'],
      NULL,
      \InvalidArgumentException::class,
      'Field value cannot mix positional and named keys',
    ];
    yield 'record missing main property rejected' => [
      ['summary' => 'short'],
      NULL,
      \InvalidArgumentException::class,
      'Field record must include the main property "value"',
    ];
  }

}
