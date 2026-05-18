<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Smartdate field handler for the smart_date contrib module.
 *
 * Smart date fields store six columns: 'value' and 'end_value' as Unix
 * integer timestamps, 'duration' in whole minutes, 'rrule' and
 * 'rrule_index' for recurring events, and 'timezone' as a string. Accepts
 * numeric timestamps straight through and falls back to 'strtotime()' for
 * human-readable date strings (matching 'TimeHandler' for parity). When the
 * caller supplies both endpoints but no duration, derives it from
 * '(end - start) / 60' clamped at zero.
 */
class SmartdateHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   *
   * Accepts: a single positional pair '[start, end]', a single keyed
   * record '['value' => ..., 'end_value' => ..., ...]', or a list of
   * either form for multi-delta fields. Returns one storage record per
   * input delta.
   */
  public function expand($values): array {
    if (!is_array($values) || $values === []) {
      return [];
    }

    $records = (isset($values[0]) && is_array($values[0])) ? $values : [$values];
    $expanded = [];

    foreach ($records as $record) {
      if (!is_array($record)) {
        continue;
      }

      $start = $this->toTimestamp($record['value'] ?? $record[0] ?? NULL);
      $end = $this->toTimestamp($record['end_value'] ?? $record[1] ?? NULL);

      $duration = $record['duration'] ?? NULL;
      if ($duration === NULL && $start !== NULL && $end !== NULL) {
        $duration = (int) max(0, ($end - $start) / 60);
      }

      $expanded[] = [
        'value' => $start,
        'end_value' => $end,
        'duration' => $duration !== NULL ? (int) $duration : 0,
        'rrule' => $record['rrule'] ?? NULL,
        'rrule_index' => $record['rrule_index'] ?? NULL,
        'timezone' => $record['timezone'] ?? '',
      ];
    }

    return $expanded;
  }

  /**
   * Normalises a start/end input into a Unix timestamp.
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
