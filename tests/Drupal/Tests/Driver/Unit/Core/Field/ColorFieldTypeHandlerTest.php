<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\ColorFieldTypeHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the ColorFieldTypeHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class ColorFieldTypeHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $reflection = new \ReflectionClass(ColorFieldTypeHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $property->setValue($handler, 'color');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'bare scalar maps to color column' => [
      '#1A2B3C',
      [['color' => '#1A2B3C']],
      NULL,
      NULL,
    ];
    yield 'list of scalars is multiple color deltas' => [
      ['#1A2B3C', '#FFFFFF'],
      [['color' => '#1A2B3C'], ['color' => '#FFFFFF']],
      NULL,
      NULL,
    ];
    yield 'single record with color and opacity' => [
      ['color' => '#1A2B3C', 'opacity' => 0.5],
      [['color' => '#1A2B3C', 'opacity' => 0.5]],
      NULL,
      NULL,
    ];
    yield 'opacity omitted when not supplied' => [
      ['color' => '#1A2B3C'],
      [['color' => '#1A2B3C']],
      NULL,
      NULL,
    ];
    yield 'list of records' => [
      [['color' => '#1A2B3C', 'opacity' => 0.5], ['color' => '#FFFFFF']],
      [['color' => '#1A2B3C', 'opacity' => 0.5], ['color' => '#FFFFFF']],
      NULL,
      NULL,
    ];

    yield 'mixed positional and named keys rejected' => [
      ['#1A2B3C', 'opacity' => 0.5],
      NULL,
      \InvalidArgumentException::class,
      'Field value cannot mix positional and named keys',
    ];
    yield 'record missing main property rejected' => [
      ['opacity' => 0.5],
      NULL,
      \InvalidArgumentException::class,
      'Field record must include the main property "color"',
    ];
  }

}
