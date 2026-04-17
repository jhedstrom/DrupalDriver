<?php

declare(strict_types=1);

namespace Drupal\Driver;

/**
 * Implements DriverInterface.
 */
class BlackboxDriver extends BaseDriver {

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped(): bool {
    // Assume the blackbox is always bootstrapped.
    return TRUE;
  }

}
