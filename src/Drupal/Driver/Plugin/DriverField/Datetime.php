<?php
namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginBase;

/**
 * A driver field plugin for datetime fields.
 *
 * @DriverField(
 *   id = "datetime",
 *   fieldTypes = {
 *     "datetime",
 *   },
 *   weight = -100,
 * )
 */
class Datetime extends DriverFieldPluginBase {

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
      $processedValue= str_replace(' ', 'T', $value['value']);
    }
    return ['value' => $processedValue];
  }
}