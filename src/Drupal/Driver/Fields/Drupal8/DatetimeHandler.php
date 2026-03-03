<?php

namespace Drupal\Driver\Fields\Drupal8;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Datetime field handler for Drupal 8.
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $siteTimezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default'));
    $storageTimezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    foreach ($values as $key => $value) {
      if (strpos($value, "relative:") !== FALSE) {
        $value = trim(str_replace('relative:', '', $value));
      }

      if ($this->fieldInfo->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
        // A date only value should be in the format used for date storage but
        // in the site timezone.
        $date = new DrupalDateTime($value, $siteTimezone);
        $format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
      }
      else {
        // A datetime value is assumed to be provided in the site timezone and
        // must be transformed into the storage timezone.
        $date = new DrupalDateTime($value, $siteTimezone);
        $date->setTimezone($storageTimezone);
        $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
      }
      $values[$key] = $date->format($format);
    }
    return $values;
  }

}
