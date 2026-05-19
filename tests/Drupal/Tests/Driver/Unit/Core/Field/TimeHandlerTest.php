<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\TimeHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TimeHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class TimeHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $reflection = new \ReflectionClass(TimeHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $property->setValue($handler, 'value');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    $midnight = strtotime('today midnight');

    yield 'numeric integer passes through' => [
      [34200],
      [34200],
      NULL,
      NULL,
    ];
    yield 'numeric string passes through' => [
      ['34200'],
      ['34200'],
      NULL,
      NULL,
    ];
    yield 'bare numeric integer' => [
      34200,
      [34200],
      NULL,
      NULL,
    ];
    yield 'strtotime string 9:30 AM' => [
      ['9:30 AM'],
      [strtotime('9:30 AM') - $midnight],
      NULL,
      NULL,
    ];
    yield 'strtotime string 14:15:30' => [
      ['14:15:30'],
      [strtotime('14:15:30') - $midnight],
      NULL,
      NULL,
    ];
    yield 'midnight resolves to zero' => [
      ['midnight'],
      [0],
      NULL,
      NULL,
    ];
    yield 'mixed list of int and strings' => [
      [3600, '9:30 AM', '0'],
      [3600, strtotime('9:30 AM') - $midnight, '0'],
      NULL,
      NULL,
    ];

    yield 'mixed positional and named keys rejected' => [
      [3600, 'extra' => 'unexpected'],
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
