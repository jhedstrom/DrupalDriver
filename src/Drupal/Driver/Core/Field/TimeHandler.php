<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'time' fields.
 */
class TimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $midnight = strtotime('today midnight');
    $seconds = [];

    foreach ($records as $record) {
      $value = $record['value'];

      if (is_numeric($value)) {
        $seconds[] = $value;
        continue;
      }

      $timestamp = strtotime((string) $value);

      if ($timestamp === FALSE) {
        throw new \InvalidArgumentException(sprintf('Time field value "%s" is not parseable.', (string) $value));
      }

      $seconds[] = $timestamp - $midnight;
    }

    return $seconds;
  }

}
