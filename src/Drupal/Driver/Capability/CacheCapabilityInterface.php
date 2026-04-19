<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: clear Drupal and static caches.
 */
interface CacheCapabilityInterface {

  /**
   * Clears Drupal caches.
   *
   * @param string|null $type
   *   Cache bin to clear. NULL or 'all' clears everything.
   */
  public function cacheClear(?string $type = NULL): void;

  /**
   * Clears static caches.
   */
  public function cacheClearStatic(): void;

}
