<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'color_field_type' fields (color_field contrib module).
 *
 * The base normalise() folds the bare-scalar 'color' shorthand and keyed
 * 'color'/'opacity' records, and the field type's preSave() owns hex
 * formatting and the opacity-disabled case, so the handler only relays the
 * multi-column records through.
 *
 * @deprecated in drupal-driver:3.x and is removed from drupal-driver:4.0.0.
 *   The 'color'/'opacity' columns are plain scalars the generic DefaultHandler
 *   now relays, so this pass-through handler is redundant. It is retained for
 *   consumers that extend or reference it. Register a dedicated handler only
 *   for a field type whose author-facing input differs from its stored value.
 *
 * @see \Drupal\Driver\Core\Field\DefaultHandler
 * @see https://www.drupal.org/project/color_field
 */
class ColorFieldTypeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    return $records;
  }

}
