<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Driver\Core\Field\NameHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the NameHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class NameHandlerTest extends TestCase {

  /**
   * All six name components, all enabled (the module's default).
   *
   * @var array<string, bool>
   */
  protected const ALL_ENABLED = [
    'title' => TRUE,
    'given' => TRUE,
    'middle' => TRUE,
    'family' => TRUE,
    'generational' => TRUE,
    'credentials' => TRUE,
  ];

  /**
   * Tests name field expansion with all components enabled.
   *
   * @param array<int, mixed> $input
   *   The input values to expand.
   * @param array<int, mixed> $expected
   *   The expected expanded values.
   */
  #[DataProvider('dataProviderExpand')]
  public function testExpand(array $input, array $expected): void {
    $handler = $this->createHandler();
    $result = $handler->expand($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpand().
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'string shorthand family, given' => [
      ['Doe, John'],
      [['family' => 'Doe', 'given' => 'John']],
    ];
    yield 'string shorthand family only' => [
      ['Doe'],
      [['family' => 'Doe', 'given' => NULL]],
    ];
    yield 'named keys' => [
      [['given' => 'John', 'family' => 'Doe', 'middle' => 'Q']],
      [['given' => 'John', 'family' => 'Doe', 'middle' => 'Q']],
    ];
    yield 'numeric indices' => [
      [['Dr', 'John', 'Quincy', 'Doe']],
      [['title' => 'Dr', 'given' => 'John', 'middle' => 'Quincy', 'family' => 'Doe']],
    ];
    yield 'multiple values' => [
      [
        'Doe, John',
        ['given' => 'Jane', 'family' => 'Smith'],
      ],
      [
        ['family' => 'Doe', 'given' => 'John'],
        ['given' => 'Jane', 'family' => 'Smith'],
      ],
    ];
  }

  /**
   * Tests that numeric indices skip components disabled on the field.
   */
  public function testNumericIndicesSkipDisabledComponents(): void {
    $handler = $this->createHandler([
      'title' => TRUE,
      'given' => TRUE,
      'middle' => FALSE,
      'family' => TRUE,
      'generational' => FALSE,
      'credentials' => FALSE,
    ]);

    $result = $handler->expand([['Dr', 'John', 'Doe']]);

    $this->assertSame([
      ['title' => 'Dr', 'given' => 'John', 'family' => 'Doe'],
    ], $result);
  }

  /**
   * Tests that excess numeric indices throw.
   */
  public function testTooManyNumericIndicesThrows(): void {
    $handler = $this->createHandler([
      'title' => FALSE,
      'given' => TRUE,
      'middle' => FALSE,
      'family' => TRUE,
      'generational' => FALSE,
      'credentials' => FALSE,
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Too many name sub-field values supplied; only 2 enabled components available.');

    $handler->expand([['John', 'Doe', 'Extra']]);
  }

  /**
   * Tests that a named key targeting a disabled component throws.
   */
  public function testNamedKeyForDisabledComponentThrows(): void {
    $handler = $this->createHandler([
      'title' => FALSE,
      'given' => TRUE,
      'middle' => FALSE,
      'family' => TRUE,
      'generational' => FALSE,
      'credentials' => FALSE,
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Cannot set the "middle" name component because it is disabled on this field.');

    $handler->expand([['given' => 'John', 'middle' => 'Q', 'family' => 'Doe']]);
  }

  /**
   * Tests that an unknown sub-field key throws.
   */
  public function testUnknownKeyThrows(): void {
    $handler = $this->createHandler();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid name sub-field key: nickname.');

    $handler->expand([['nickname' => 'Johnny']]);
  }

  /**
   * Tests that the shorthand throws when family is disabled.
   */
  public function testShorthandThrowsWhenFamilyDisabled(): void {
    $handler = $this->createHandler([
      'title' => TRUE,
      'given' => TRUE,
      'middle' => FALSE,
      'family' => FALSE,
      'generational' => FALSE,
      'credentials' => FALSE,
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Cannot use the "Family, Given" shorthand because the "family" component is disabled on this field.');

    $handler->expand(['Doe, John']);
  }

  /**
   * Tests that the shorthand throws when a given part is supplied but disabled.
   */
  public function testShorthandThrowsWhenGivenPartSuppliedButDisabled(): void {
    $handler = $this->createHandler([
      'title' => FALSE,
      'given' => FALSE,
      'middle' => FALSE,
      'family' => TRUE,
      'generational' => FALSE,
      'credentials' => FALSE,
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Cannot use the "Family, Given" shorthand because the "given" component is disabled on this field.');

    $handler->expand(['Doe, John']);
  }

  /**
   * Tests the family-only shorthand when 'given' is disabled.
   */
  public function testShorthandFamilyOnlyWhenGivenDisabled(): void {
    $handler = $this->createHandler([
      'title' => FALSE,
      'given' => FALSE,
      'middle' => FALSE,
      'family' => TRUE,
      'generational' => FALSE,
      'credentials' => FALSE,
    ]);

    $result = $handler->expand(['Doe']);

    $this->assertSame([['family' => 'Doe']], $result);
  }

  /**
   * Creates a NameHandler with an injected fieldConfig mock.
   *
   * @param array<string, bool> $components
   *   Map of component name to enabled flag. Defaults to all enabled.
   *
   * @return \Drupal\Driver\Core\Field\NameHandler
   *   The handler instance.
   */
  protected function createHandler(array $components = self::ALL_ENABLED): NameHandler {
    $field_config = $this->createMock(FieldDefinitionInterface::class);
    $field_config->method('getSettings')->willReturn(['components' => $components]);

    $reflection = new \ReflectionClass(NameHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(NameHandler::class, 'fieldConfig');
    $property->setValue($handler, $field_config);

    return $handler;
  }

}
