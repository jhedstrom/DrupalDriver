<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for the Driver's field plugins.
 */
interface DriverFieldPluginInterface extends PluginInspectionInterface
{

  /**
   * Converts a set of string instructions into a set of field values.
   *
   * @return array
   *   returns the array of field values, one for each cardinality.
   */
    public function processValues($field);

  /**
   * Indicates whether lower-priority plugins should be called or if field
   * processing should finish with this plugin.
   *
   * @return boolean
   *   whether processing should finish with this plugin.
   */
    public function isFinal($value);
}
