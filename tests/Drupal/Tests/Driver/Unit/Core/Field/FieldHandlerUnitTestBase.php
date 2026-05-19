<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Base unit test for field handlers.
 *
 * Subclasses supply 'createHandler()' and 'dataProviderExpand()'.
 *
 * Data provider rows have the shape:
 *   [input, expected, exception_class_or_NULL, exception_message_or_NULL]
 *
 *   - On the happy path: 'expected' is the asserted storage shape;
 *     'exception_class' is NULL.
 *   - On the error path: 'expected' is NULL and 'exception_class' is the
 *     class that must be thrown ('exception_message' optionally pins a
 *     substring).
 */
abstract class FieldHandlerUnitTestBase extends TestCase {

  /**
   * Produces the configured handler under test.
   */
  abstract protected function createHandler(): FieldHandlerInterface;

  /**
   * Tests 'expand()' end-to-end with caller-shaped input.
   *
   * @param mixed $input
   *   The loose input fed to 'expand()'.
   * @param mixed $expected
   *   The expected storage-shape output, or NULL when an exception is
   *   expected.
   * @param string|null $exception
   *   The expected exception class, or NULL for the happy path.
   * @param string|null $exception_message
   *   Substring the exception message must contain, or NULL.
   *
   * @dataProvider dataProviderExpand
   */
  #[DataProvider('dataProviderExpand')]
  public function testExpand(mixed $input, mixed $expected, ?string $exception, ?string $exception_message): void {
    $handler = $this->createHandler();

    if ($exception !== NULL) {
      $this->expectException($exception);

      if ($exception_message !== NULL) {
        $this->expectExceptionMessage($exception_message);
      }
    }

    // Only suppress PHP warnings on rows that expect an exception (e.g.
    // 'file_get_contents()' raises a warning before the handler throws);
    // success-path rows must not silently swallow unexpected warnings.
    $result = $exception !== NULL
      ? @$handler->expand($input)
      : $handler->expand($input);

    if ($exception === NULL) {
      $this->assertSame($expected, $result);
    }
  }

  /**
   * Data provider for 'testExpand()'.
   *
   * @return \Iterator<string, array{mixed, mixed, ?string, ?string}>
   *   Rows of: input, expected storage shape, exception class, message
   *   substring.
   */
  abstract public static function dataProviderExpand(): \Iterator;

}
