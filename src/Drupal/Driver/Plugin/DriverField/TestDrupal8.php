<?php
namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal8Base;

/**
 * A driver field plugin for testing purposes.
 *
 * @DriverField(
 *   id = "test",
 *   version = 8,
 *   fieldNames = {
 *     "name",
 *     "test_field_name",
 *     "field_test_field_name",
 *   },
 *   fieldTypes = {
 *     "string",
 *   },
 *   entityTypes = {
 *     "entity_test",
 *     "entity_test_with_bundle",
 *   },
 *   weight = -100,
 * )
 */
class TestDrupal8 extends DriverFieldPluginDrupal8Base {

  /**
   * {@inheritdoc}
   */
  protected function processValue($value) {
    return ['value' => 'now' . $value['value'] . 'processed'];
  }
}