<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'date_recur' fields (date_recur contrib module).
 *
 * The base normalise() folds the bare-scalar 'value' shorthand and keyed
 * multi-column records ('value', 'end_value', 'rrule', 'timezone',
 * 'infinite'). The 'value'/'end_value' columns are stored verbatim and
 * interpreted in the record's own 'timezone' (not UTC), and the field type's
 * preSave() derives 'infinite' from the rrule, so the handler only relays the
 * multi-column records through.
 *
 * @see https://www.drupal.org/project/date_recur
 */
class DateRecurHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    return $records;
  }

}
