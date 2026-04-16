<?php

namespace Drupal\Driver\Fields\Drupal8;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Daterange field handler for Drupal 8.
 */
class DaterangeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $siteTimezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default'));
    $storageTimezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $return = [];

    foreach ($values as $value) {
      // Support both named keys and numeric indices.
      $start = $value['value'] ?? $value[0] ?? NULL;
      $end = $value['end_value'] ?? $value[1] ?? NULL;

      $return[] = [
        'value' => $start ? $this->formatDate($start, $siteTimezone, $storageTimezone) : NULL,
        'end_value' => $end ? $this->formatDate($end, $siteTimezone, $storageTimezone) : NULL,
      ];
    }

    return $return;
  }

  /**
   * Formats a date value for storage.
   *
   * @param string $value
   *   The date value to format.
   * @param \DateTimeZone $site_timezone
   *   The site timezone.
   * @param \DateTimeZone $storage_timezone
   *   The storage timezone.
   *
   * @return string
   *   The formatted date string.
   */
  protected function formatDate($value, \DateTimeZone $site_timezone, \DateTimeZone $storage_timezone) {
    if (strpos($value, 'relative:') !== FALSE) {
      $value = trim(str_replace('relative:', '', $value));
    }

    if ($this->fieldInfo->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $date = new DrupalDateTime($value, $site_timezone);
      $format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    }
    else {
      $date = new DrupalDateTime($value, $site_timezone);
      $date->setTimezone($storage_timezone);
      $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    }

    return $date->format($format);
  }

}
