<?php

declare(strict_types=1);

namespace Drupal\Driver\Core99;

use Drupal\Driver\Core\Core as DefaultCore;

/**
 * Fixture: simulated Core99 override.
 *
 * Extends the default Core, used by lookup-chain tests to verify that
 * DrupalDriver::setCoreFromVersion() picks up version-specific overrides
 * when they exist.
 */
class Core extends DefaultCore {

  /**
   * Marker so tests can identify which class was instantiated.
   */
  public const MARKER = 'Core99\\Core';

  /**
   * {@inheritdoc}
   */
  protected function getVersion(): int {
    return 99;
  }

}
