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
    $site_timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default'));
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

    foreach ($values as $key => $value) {
      $values[$key] = $this->formatDateValue($value, $site_timezone, $storage_timezone);
    }

    return $values;
  }

  /**
   * Formats a date value for storage based on the field's datetime_type.
   *
   * @param string $value
   *   The raw date value, optionally prefixed with "relative:".
   * @param \DateTimeZone $site_timezone
   *   The site timezone.
   * @param \DateTimeZone $storage_timezone
   *   The storage timezone.
   *
   * @return string
   *   The formatted date string.
   */
  protected function formatDateValue($value, \DateTimeZone $site_timezone, \DateTimeZone $storage_timezone) {
    if (strpos($value, 'relative:') !== FALSE) {
      $value = trim(str_replace('relative:', '', $value));
    }

    $is_date_only = $this->fieldInfo->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE;

    $date = new DrupalDateTime($value, $site_timezone);

    if ($is_date_only) {
      return $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }

    $date->setTimezone($storage_timezone);

    return $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
  }

}
