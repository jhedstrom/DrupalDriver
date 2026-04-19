<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Driver\Capability\CacheCapabilityInterface;
use Drupal\Driver\Capability\ConfigCapabilityInterface;
use Drupal\Driver\Capability\CronCapabilityInterface;
use Drupal\Driver\Capability\ModuleCapabilityInterface;
use Drupal\Driver\Capability\RoleCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Capability\WatchdogCapabilityInterface;

/**
 * Contract for the Drush-based driver.
 *
 * Interacts with the site by shelling out to Drush. Supports the subset of
 * operations that Drush services natively through its built-in commands.
 */
interface DrushDriverInterface extends
  DriverInterface,
  CacheCapabilityInterface,
  ConfigCapabilityInterface,
  CronCapabilityInterface,
  ModuleCapabilityInterface,
  RoleCapabilityInterface,
  UserCapabilityInterface,
  WatchdogCapabilityInterface {

}
