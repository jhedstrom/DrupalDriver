<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for the Driver's plugin managers.
 */
interface DriverPluginManagerInterface extends PluginManagerInterface
{

  /**
   * Get plugin definitions matching a target, sorted by weight and specificity.
   *
   * @param array|object $rawTarget
   *   An array or object that is the target to match definitions against.
   *
   * @return array
   *   An array of sorted plugin definitions that match that target.
   */
    public function getMatchedDefinitions($rawTarget);
}
