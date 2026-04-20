<?php

declare(strict_types=1);

namespace ConsumerProject\Driver\Field;

use Drupal\Driver\Core\Field\AbstractHandler;

/**
 * Fixture: consumer-side override for the 'text_long' handler.
 *
 * The library ships its own 'TextLongHandler' in 'Drupal\Driver\Core\Field';
 * this one shares the basename but lives in the consumer's namespace. When
 * 'ConsumerCore::registerDefaultFieldHandlers()' calls the parent first and
 * then re-scans its own 'Field/' directory, this registration wins because
 * 'registerFieldHandler()' is last-write-wins on the field type key.
 */
class TextLongHandler extends AbstractHandler {

  /**
   * Marker value the test asserts appears in storage.
   */
  public const MARKER = 'consumer text_long override';

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
