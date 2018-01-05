<?php
namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginBase;

/**
 * A driver field plugin for testing purposes.
 *
 * @DriverField(
 *   id = "test",
 *   fieldNames = {
 *     "name",
 *     "field2",
 *   },
 *   fieldTypes = {
 *     "string",
 *   },
 *   entityTypes = {
 *     "entity_test",
 *   },
 *   weight = -100,
 * )
 */
class Test extends DriverFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function processValue($value) {
    return ['value' => 'now' . $value['value'] . 'processed'];
  }
}