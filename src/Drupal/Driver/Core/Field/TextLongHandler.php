<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Pass-through handler for 'text_long' fields.
 *
 * Stores (value, format) per delta; used for taxonomy term description,
 * paragraph body, custom block body. DefaultHandler cannot marshal it
 * because it is multi-column, so a dedicated pass-through is required.
 */
class TextLongHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand(mixed $values): array {
    return $this->normalise($values);
  }

}
