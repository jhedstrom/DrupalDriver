<?php

declare(strict_types=1);

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Time field handler for Drupal 8.
 */
class TimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    return array_map(function ($value) {
      // Value is numeric so it is safe to assume we have the seconds passed in
      // the storage format (seconds past midnight).
      if (is_numeric($value)) {
        return $value;
      }

      // Support anything that can be passed to strtotime.
      $midnight = strtotime('today midnight');
      return strtotime($value) - $midnight;
    }, $values);
  }

}
