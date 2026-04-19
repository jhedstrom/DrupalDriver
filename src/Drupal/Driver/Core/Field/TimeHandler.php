<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Time field handler for Drupal 8.
 */
class TimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $seconds = [];

    foreach ($values as $value) {
      // Numeric values are already in storage format (seconds past midnight).
      if (is_numeric($value)) {
        $seconds[] = $value;
        continue;
      }

      // Support anything that can be passed to strtotime.
      $midnight = strtotime('today midnight');
      $seconds[] = strtotime((string) $value) - $midnight;
    }

    return $seconds;
  }

}
