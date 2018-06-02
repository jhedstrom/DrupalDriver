<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal8Base;

/**
 * A driver field plugin for datetime fields.
 *
 * @DriverField(
 *   id = "datetime",
 *   version = 8,
 *   fieldTypes = {
 *     "datetime",
 *   },
 *   weight = -100,
 * )
 */
class DatetimeDrupal8 extends DriverFieldPluginDrupal8Base {

  /**
   * {@inheritdoc}
   */
  protected function processValue($value) {
    if (strpos($value['value'], "relative:") !== FALSE) {
      $relative = trim(str_replace('relative:', '', $value['value']));
      // Get time, convert to ISO 8601 date in GMT/UTC, remove TZ offset.
      $processedValue = substr(gmdate('c', strtotime($relative)), 0, 19);
    }
    else {
      $processedValue = str_replace(' ', 'T', $value['value']);
    }
    return ['value' => $processedValue];
  }

}
