<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: run cron.
 */
interface CronCapabilityInterface {

  /**
   * Runs cron.
   *
   * @return bool
   *   TRUE if cron ran successfully.
   */
  public function cronRun(): bool;

}
