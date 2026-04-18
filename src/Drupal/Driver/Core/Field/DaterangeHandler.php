<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Daterange field handler for Drupal 8.
 *
 * Extends DatetimeHandler to reuse date formatting logic.
 */
class DaterangeHandler extends DatetimeHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $site_timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default') ?: 'UTC');
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $result = [];

    foreach ($values as $value) {
      $start = $value['value'] ?? $value[0] ?? NULL;
      $end = $value['end_value'] ?? $value[1] ?? NULL;

      $result[] = [
        'value' => $start ? $this->formatDateValue($start, $site_timezone, $storage_timezone) : NULL,
        'end_value' => $end ? $this->formatDateValue($end, $site_timezone, $storage_timezone) : NULL,
      ];
    }

    return $result;
  }

}
