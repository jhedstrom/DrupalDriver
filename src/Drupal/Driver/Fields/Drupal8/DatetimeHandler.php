<?php

namespace Drupal\Driver\Fields\Drupal8;

use DateTime;
use DateTimeZone;

/**
 * Datetime field handler for Drupal 8.
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $siteTimezone = new DateTimeZone(\Drupal::config('system.date')->get('timezone.default'));
    $storageTimezone = new DateTimeZone(DATETIME_STORAGE_TIMEZONE);
    foreach ($values as $key => $value) {
      if (strpos($value, "relative:") !== FALSE) {
        $relative = trim(str_replace('relative:', '', $value));
        // Get time, convert to ISO 8601 date in GMT/UTC, remove TZ offset.
        $values[$key] = substr(gmdate('c', strtotime($relative)), 0, 19);
      }
      else {
        // A Drupal install has a default site timezone, but nonetheless
        // uses UTC for internal storage. If no timezone is specified in a date
        // field value by the step author, assume the default timezone of
        // the Drupal install, and therefore transform it into UTC for storage.
        if (DateTime::createFromFormat('Y-m-d', $value) !== FALSE) {
          // Handle 'Date only' date type.
          $date = new DateTime($value);
          $formattedDate = $date->format('Y-m-d');
        }
        else {
          $date = new DateTime($value, $siteTimezone);
          $date->setTimezone($storageTimezone);
          $formattedDate = $date->format('Y-m-d\TH:i:s');
        }
        $values[$key] = $formattedDate;
      }
    }
    return $values;
  }

}
