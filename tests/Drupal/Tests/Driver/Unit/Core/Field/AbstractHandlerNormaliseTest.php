<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\AbstractHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests AbstractHandler::normalise() across every input shape we accept.
 *
 * @group fields
 */
#[Group('fields')]
class AbstractHandlerNormaliseTest extends TestCase {

  /**
   * Tests every accepted and rejected input shape for normalise().
   *
   * Each data row carries the input, the field's main property name, the
   * expected output (or NULL when an exception is expected), the expected
   * exception class (or NULL for the happy path), and the expected
   * substring of the exception message (or NULL).
   *
   * @param mixed $input
   *   The loose input to feed to normalise().
   * @param string $main_property
   *   The field's main property name (returned by the mocked fieldInfo).
   * @param array<int, array<string, mixed>>|null $expected
   *   The expected canonical list of records, or NULL when expecting a throw.
   * @param string|null $exception
   *   The expected exception class, or NULL for the happy path.
   * @param string|null $exception_message
   *   Substring the exception message must contain, or NULL.
   *
   * @dataProvider dataProviderNormalise
   */
  #[DataProvider('dataProviderNormalise')]
  public function testNormalise(mixed $input, string $main_property, ?array $expected, ?string $exception, ?string $exception_message): void {
    $handler = $this->createHandler($main_property);

    if ($exception !== NULL) {
      $this->expectException($exception);

      if ($exception_message !== NULL) {
        $this->expectExceptionMessage($exception_message);
      }
    }

    $result = $this->invokeNormalise($handler, $input);

    if ($exception === NULL) {
      $this->assertSame($expected, $result);
    }
  }

  /**
   * Data provider for testNormalise().
   *
   * Covers happy paths (every loose input shape the helper must accept)
   * and error paths (every malformed shape the helper must reject).
   */
  public static function dataProviderNormalise(): \Iterator {
    yield 'bare string scalar with target_id main' => [
      'foo.jpg',
      'target_id',
      [['target_id' => 'foo.jpg']],
      NULL,
      NULL,
    ];
    yield 'bare integer scalar with value main' => [
      42,
      'value',
      [['value' => 42]],
      NULL,
      NULL,
    ];
    yield 'bare NULL scalar' => [
      NULL,
      'value',
      [['value' => NULL]],
      NULL,
      NULL,
    ];
    yield 'empty array' => [
      [],
      'value',
      [],
      NULL,
      NULL,
    ];
    yield 'list of one scalar' => [
      ['foo.jpg'],
      'target_id',
      [['target_id' => 'foo.jpg']],
      NULL,
      NULL,
    ];
    yield 'list of multiple scalars' => [
      ['a.jpg', 'b.jpg'],
      'target_id',
      [['target_id' => 'a.jpg'], ['target_id' => 'b.jpg']],
      NULL,
      NULL,
    ];
    yield 'single record (assoc array)' => [
      ['target_id' => 'foo.jpg', 'alt' => 'A', 'title' => 'B'],
      'target_id',
      [['target_id' => 'foo.jpg', 'alt' => 'A', 'title' => 'B']],
      NULL,
      NULL,
    ];
    yield 'list of one record' => [
      [['target_id' => 'foo.jpg', 'alt' => 'A']],
      'target_id',
      [['target_id' => 'foo.jpg', 'alt' => 'A']],
      NULL,
      NULL,
    ];
    yield 'list of multiple records' => [
      [
        ['target_id' => 'a.jpg', 'alt' => 'A'],
        ['target_id' => 'b.jpg', 'alt' => 'B'],
      ],
      'target_id',
      [
        ['target_id' => 'a.jpg', 'alt' => 'A'],
        ['target_id' => 'b.jpg', 'alt' => 'B'],
      ],
      NULL,
      NULL,
    ];
    yield 'mixed list of scalars and records' => [
      ['plain', ['value' => 'rich', 'format' => 'basic_html']],
      'value',
      [['value' => 'plain'], ['value' => 'rich', 'format' => 'basic_html']],
      NULL,
      NULL,
    ];
    yield 'uri main property (link)' => [
      'https://example.com',
      'uri',
      [['uri' => 'https://example.com']],
      NULL,
      NULL,
    ];
    yield 'list with NULL scalar' => [
      [NULL, 'something'],
      'value',
      [['value' => NULL], ['value' => 'something']],
      NULL,
      NULL,
    ];
    yield 'rejects numeric 0 followed by named extras' => [
      ['/path/foo.jpg', 'alt' => 'A'],
      'target_id',
      NULL,
      \InvalidArgumentException::class,
      'Got keys: 0, alt.',
    ];
    yield 'rejects numeric 0 with multiple named extras' => [
      ['/path/foo.jpg', 'alt' => 'A', 'title' => 'B'],
      'target_id',
      NULL,
      \InvalidArgumentException::class,
      'Got keys: 0, alt, title.',
    ];
    yield 'rejects named keys followed by numeric' => [
      ['alt' => 'A', 0 => '/path/foo.jpg'],
      'target_id',
      NULL,
      \InvalidArgumentException::class,
      'Got keys: alt, 0.',
    ];
    yield 'rejects gappy numeric mixed with named' => [
      [2 => 'a', 'alt' => 'A'],
      'value',
      NULL,
      \InvalidArgumentException::class,
      'Got keys: 2, alt.',
    ];
  }

  /**
   * Invokes the protected normalise() method on the given handler.
   *
   * @return array<int, array<string, mixed>>
   *   The canonical list of records returned by normalise().
   */
  protected function invokeNormalise(AbstractHandler $handler, mixed $input): array {
    $method = new \ReflectionMethod(AbstractHandler::class, 'normalise');
    return $method->invoke($handler, $input);
  }

  /**
   * Creates an AbstractHandler subclass with a stubbed fieldInfo.
   *
   * Bypasses the constructor (which requires a full Drupal entity bootstrap)
   * and injects a fieldInfo mock whose getMainPropertyName() returns the
   * given value.
   */
  protected function createHandler(string $main_property): AbstractHandler {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getMainPropertyName')->willReturn($main_property);

    $handler = (new \ReflectionClass(NormaliseTestHandler::class))->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(AbstractHandler::class, 'fieldInfo');
    $property->setValue($handler, $field_info);

    return $handler;
  }

}

/**
 * Concrete AbstractHandler subclass used only by the normalise() tests.
 *
 * Provides the abstract surface (expand()) without doing any real work,
 * so reflection-based tests can exercise the inherited normalise() helper
 * in isolation from any specific field handler's resolution behaviour.
 */
final class NormaliseTestHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand(mixed $values): array {
    return $this->normalise($values);
  }

}
