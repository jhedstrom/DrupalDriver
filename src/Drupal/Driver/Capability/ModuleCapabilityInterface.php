<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: install and uninstall modules.
 */
interface ModuleCapabilityInterface {

  /**
   * Installs a module.
   *
   * @param string $module_name
   *   The module machine name.
   */
  public function moduleInstall(string $module_name): void;

  /**
   * Uninstalls a module.
   *
   * @param string $module_name
   *   The module machine name.
   */
  public function moduleUninstall(string $module_name): void;

}
