<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Pass-through handler for 'text' fields.
 *
 * Stores (value, format) per delta; 'text' is the one-line counterpart to
 * 'text_long'. Both share the multi-column shape and therefore need a
 * dedicated pass-through so DefaultHandler does not reject the payload.
 */
class TextHandler implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function expand(mixed $values): array {
    return (array) $values;
  }

}
