<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'smartdate' fields (smart_date contrib module).
 */
class SmartdateHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function normalise(mixed $values): array {
    if (!is_array($values) || $values === []) {
      return [];
    }

    // A list whose first element is an array is treated as a list of
    // records; anything else is a single delta wrapped in a list.
    $is_list_of_records = array_is_list($values) && is_array($values[0]);

    if (!$is_list_of_records) {
      $values = [$values];
    }

    $records = [];

    foreach ($values as $value) {
      if (!is_array($value)) {
        throw new \InvalidArgumentException(sprintf(
          'Smartdate field delta must be an array (positional [start, end] or keyed value/end_value). Got %s.',
          get_debug_type($value),
        ));
      }

      $records[] = [
        'value' => $value['value'] ?? $value[0] ?? NULL,
        'end_value' => $value['end_value'] ?? $value[1] ?? NULL,
        'duration' => $value['duration'] ?? NULL,
        'rrule' => $value['rrule'] ?? NULL,
        'rrule_index' => $value['rrule_index'] ?? NULL,
        'timezone' => $value['timezone'] ?? NULL,
      ];
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $expanded = [];

    foreach ($records as $record) {
      $start = $this->toTimestamp($record['value']);
      $end = $this->toTimestamp($record['end_value']);

      $duration = $record['duration'];

      if ($duration === NULL && $start !== NULL && $end !== NULL) {
        $duration = (int) max(0, ($end - $start) / 60);
      }

      $expanded[] = [
        'value' => $start,
        'end_value' => $end,
        'duration' => $duration !== NULL ? (int) $duration : 0,
        'rrule' => $record['rrule'],
        'rrule_index' => $record['rrule_index'],
        'timezone' => $record['timezone'] ?? '',
      ];
    }

    return $expanded;
  }

  /**
   * Coerces a start/end value into a Unix timestamp.
   *
   * @param mixed $value
   *   A numeric Unix timestamp, a 'strtotime()'-parseable date string,
   *   NULL, or an empty string.
   *
   * @return int|null
   *   The integer Unix timestamp, or NULL when the input is empty or
   *   cannot be parsed.
   */
  protected function toTimestamp(mixed $value): ?int {
    if ($value === NULL || $value === '') {
      return NULL;
    }

    if (is_numeric($value)) {
      return (int) $value;
    }

    $timestamp = strtotime((string) $value);

    return $timestamp === FALSE ? NULL : $timestamp;
  }

}
