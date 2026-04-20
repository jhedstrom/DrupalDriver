<?php

declare(strict_types=1);

namespace ConsumerProject\Driver\Field;

use Drupal\Driver\Core\Field\AbstractHandler;

/**
 * Fixture: consumer handler for a field type the library does not cover.
 *
 * The library ships no 'StringLongHandler', so without this fixture the
 * 'string_long' field type would fall through to 'DefaultHandler'. Adding
 * this class to the consumer's 'Field/' directory is all it takes for
 * 'ConsumerCore::registerDefaultFieldHandlers()' to pick it up and route
 * 'string_long' fields to it.
 */
class StringLongHandler extends AbstractHandler {

  /**
   * Marker value the test asserts appears in storage.
   */
  public const MARKER = 'consumer string_long handler';

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $emitted = [];

    foreach ((array) $values as $delta) {
      $delta = is_array($delta) ? $delta : ['value' => $delta];
      $delta['value'] = self::MARKER;
      $emitted[] = $delta;
    }

    return $emitted;
  }

}
