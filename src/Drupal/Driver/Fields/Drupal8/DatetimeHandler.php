<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Datetime field handler for Drupal 8.
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    foreach ($values as $key => $value) {
      if (strpos($value, "relative:") !== FALSE) {
        $relative = trim(str_replace('relative:', '', $value));
        // Get time, convert to ISO 8601 date in GMT/UTC, remove TZ offset.
        $values[$key] = substr(gmdate('c', strtotime($relative)), 0, 19);
      }
      else {
        $values[$key] = str_replace(' ', 'T', $value);
      }
    }
    return $values;
  }

}
