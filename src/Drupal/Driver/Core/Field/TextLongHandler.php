<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'text_long' fields.
 *
 * @deprecated in drupal-driver:3.x and is removed from drupal-driver:4.0.0.
 *   The 'text_long' columns are plain scalars the generic DefaultHandler now
 *   relays, so this pass-through handler is redundant. It is retained for
 *   consumers that extend or reference it. Register a dedicated handler only
 *   for a field type whose author-facing input differs from its stored value.
 *
 * @see \Drupal\Driver\Core\Field\DefaultHandler
 */
class TextLongHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    return $records;
  }

}
