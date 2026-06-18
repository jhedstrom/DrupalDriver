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
