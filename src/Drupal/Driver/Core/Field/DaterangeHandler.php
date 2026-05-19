<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Field handler for 'daterange' fields.
 */
class DaterangeHandler extends DatetimeHandler {

  /**
   * {@inheritdoc}
   */
  protected function normalise(mixed $values): array {
    if (!is_array($values) || $values === []) {
      return [];
    }

    // A top-level positional pair like ['start', 'end'] is itself a list,
    // so iterating directly would treat each scalar as its own delta and
    // reject it. Only a list whose first element is an array is treated
    // as a multi-delta list.
    $is_list_of_records = array_is_list($values) && is_array($values[0] ?? NULL);

    if (!$is_list_of_records) {
      $values = [$values];
    }

    $records = [];

    foreach ($values as $value) {
      if (!is_array($value)) {
        throw new \InvalidArgumentException(sprintf(
          'Daterange field record must be an array (positional [start, end] or keyed value/end_value). Got %s.',
          get_debug_type($value),
        ));
      }

      $records[] = [
        'value' => $value['value'] ?? $value[0] ?? NULL,
        'end_value' => $value['end_value'] ?? $value[1] ?? NULL,
      ];
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $site_timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default') ?: 'UTC');
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $result = [];

    foreach ($records as $record) {
      $start = $record['value'];
      $end = $record['end_value'];

      $result[] = [
        'value' => $start ? $this->formatDateValue($start, $site_timezone, $storage_timezone) : NULL,
        'end_value' => $end ? $this->formatDateValue($end, $site_timezone, $storage_timezone) : NULL,
      ];
    }

    return $result;
  }

}
