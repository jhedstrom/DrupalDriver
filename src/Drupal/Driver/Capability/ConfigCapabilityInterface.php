<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: read and write configuration.
 */
interface ConfigCapabilityInterface {

  /**
   * Returns a configuration value.
   *
   * @param string $name
   *   The configuration object name.
   * @param string $key
   *   The key within the configuration object. Empty for the whole object.
   *
   * @return mixed
   *   The configuration value, or NULL if not set.
   */
  public function configGet(string $name, string $key = ''): mixed;

  /**
   * Returns the original (on-disk) configuration value.
   *
   * @param string $name
   *   The configuration object name.
   * @param string $key
   *   The key within the configuration object. Empty for the whole object.
   *
   * @return mixed
   *   The original configuration value, or NULL if not set.
   */
  public function configGetOriginal(string $name, string $key = ''): mixed;

  /**
   * Sets a configuration value.
   *
   * @param string $name
   *   The configuration object name.
   * @param string $key
   *   The key within the configuration object.
   * @param mixed $value
   *   The value to store.
   */
  public function configSet(string $name, string $key, mixed $value): void;

}
