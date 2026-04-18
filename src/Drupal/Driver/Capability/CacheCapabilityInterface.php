<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: clear caches and run cron.
 */
interface CacheCapabilityInterface {

  /**
   * Clears Drupal caches.
   *
   * @param string|null $type
   *   Cache bin to clear. NULL or 'all' clears everything.
   */
  public function clearCache(?string $type = NULL): void;

  /**
   * Clears static caches.
   */
  public function clearStaticCaches(): void;

  /**
   * Runs cron.
   *
   * @return bool
   *   TRUE if cron ran successfully.
   */
  public function runCron(): bool;

}
