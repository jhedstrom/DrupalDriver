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
   * @param mixed $values
   *   A set of instructions (should be an array), one for each cardinality.
   *
   * @return array
   *   Returns the array of processed field values, one for each cardinality.
   */
  public function processValues($values);

  /**
   * Should field processing should finish with this plugin.
   *
   * If not, lower priority plugins will be called next.
   *
   * @return bool
   *   Whether processing should finish with this plugin.
   */
  public function isFinal();

}
