<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal8Base;

/**
 * A driver field plugin for timestamp fields.
 *
 * @DriverField(
 *   id = "timestamp8",
 *   version = 8,
 *   fieldTypes = {
 *     "timestamp",
 *     "created",
 *     "changed",
 *   },
 *   weight = -100,
 * )
 */
class TimestampDrupal8 extends DriverFieldPluginDrupal8Base {

  /**
   * {@inheritdoc}
   */
  protected function processValue($value) {
    $processedValue = $value;
    if (!empty($value['value']) && !is_numeric($value['value'])) {
      $processedValue['value'] = strtotime($value['value']);
    }
    return $processedValue;
  }

}
