<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;

/**
 * Minimum contract that every driver must satisfy.
 *
 * Additional functionality is expressed through capability interfaces in the
 * 'Drupal\Driver\Capability' namespace.
 */
interface DriverInterface {

  /**
   * Returns a random-value generator.
   */
  public function getRandom(): Random;

  /**
   * Bootstraps the driver.
   */
  public function bootstrap(): void;

  /**
   * Indicates whether the driver has been bootstrapped.
   */
  public function isBootstrapped(): bool;

}
