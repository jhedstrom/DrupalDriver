<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for the Driver's field plugins.
 */
interface DriverFieldPluginInterface extends PluginInspectionInterface {

  /**
   * Converts a set of string instructions into a set of field values.
   *
   * @return array
   *   returns the array of field values, one for each cardinality.
   */
  public function processValues($field);

  /**
   * Converts a single string instruction into a field value.
   *
   * @return array
   *   returns the array of column values for one field value.
   */
  public function processValue($value);

  /**
   * Validates that the expanded value is suitable for the field,
   * throwing an exception if not.
   */
  public function validateValues($field);

  /**
   * Indicates whether lower-priority plugins should be called or if field
   * processing should finish here.
   */
  public function isFinal($value);



}