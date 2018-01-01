<?php

namespace Drupal\Driver\Plugin;

/**
 * Provides the plugin manager for the Driver's field plugins.
 */
class DriverFieldPluginManager extends DriverPluginManagerBase {

  protected $driverPluginType = 'DriverField';

  protected $filters = [
    'fieldName',
    'fieldType',
    'entityBundle',
    'entityType'
  ];

  protected $specificityCriteria = [
    ['fieldName', 'entityBundle', 'entityType'],
    ['fieldName', 'entityBundle'],
    ['fieldName', 'entityType'],
    ['fieldName', 'fieldType'],
    ['fieldName'],
    ['fieldType', 'entityBundle'],
    ['fieldType', 'entityType'],
    ['fieldType'],
    ['entityBundle', 'entityType'],
    ['entityBundle'],
    ['entityType'],
  ];

  /**
   * Process a field for the driver, converting a human-friendly string
   * into a value for Drupal's API.
   */
  public function processValues($field) {
    $definitions = $this->getMatchedDefinitions($field);
    foreach ($definitions as $definition) {
      $plugin = $this->createInstance($definition['id']);
      $processedValues = $plugin->processValues($field->getProcessedValues());
      $field->setProcessedValues($processedValues);
      if ($plugin->isFinal($field)) {
        break;
      };
    }
    return $field;
  }


  /**
   * Convert a target object into a filterable target, an array with a key for
   * each filter.
   */
  protected function getFilterableTarget($field) {
    return [
      'fieldName' => $field->getName(),
      'fieldType' => $field->getFieldType(),
      'entityType' =>$field->getEntityType(),
      'entityBundle' => $field->getBundle()
    ];
  }

}