<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\DefaultHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the DefaultHandler field handler.
 *
 * DefaultHandler is a pure pass-through: it relays the normalised records to
 * storage unchanged. 'Core' rejects fields the default cannot marshal before it
 * resolves this handler, so that classification is exercised in FieldClassifier
 * and Core, not here.
 *
 * @group fields
 */
#[Group('fields')]
class DefaultHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    return $this->handlerWithMainProperty('value');
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
    yield 'multi-column scalar record passes through unchanged' => [
      [['value' => 'label', 'format' => 'plain_text']],
      [['value' => 'label', 'format' => 'plain_text']],
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
   * Builds a DefaultHandler with only its main property set.
   *
   * DefaultHandler's pass-through 'doExpand()' touches no field metadata, so
   * the handler needs only the main property the base 'normalise()' reads.
   */
  protected function handlerWithMainProperty(string $main_property): DefaultHandler {
    $handler = (new \ReflectionClass(DefaultHandler::class))->newInstanceWithoutConstructor();

    $main_prop = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_prop->setValue($handler, $main_property);

    return $handler;
  }

}
