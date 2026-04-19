<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: read watchdog (dblog) entries.
 */
interface WatchdogCapabilityInterface {

  /**
   * Returns recent watchdog entries as a string.
   *
   * @param int $count
   *   The maximum number of entries to return.
   * @param string|null $type
   *   Optional channel to filter by.
   * @param string|null $severity
   *   Optional severity level to filter by.
   *
   * @return string
   *   The formatted watchdog output.
   */
  public function watchdogFetch(int $count = 10, ?string $type = NULL, ?string $severity = NULL): string;

}
