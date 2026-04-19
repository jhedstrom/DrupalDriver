<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;

/**
 * Performs no backend operations.
 *
 * Useful when only the public-facing site is being exercised and no API
 * interaction is required.
 */
class BlackboxDriver implements BlackboxDriverInterface {

  /**
   * Random generator.
   */
  private readonly Random $random;

  /**
   * Set up the driver with an optional random generator.
   */
  public function __construct(?Random $random = NULL) {
    $this->random = $random ?? new Random();
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom(): Random {
    return $this->random;
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap(): void {
  }

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped(): bool {
    return TRUE;
  }

}
