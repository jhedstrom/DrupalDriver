<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Driver\Capability\AuthenticationCapabilityInterface;
use Drupal\Driver\Capability\BlockCapabilityInterface;
use Drupal\Driver\Capability\CacheCapabilityInterface;
use Drupal\Driver\Capability\ConfigCapabilityInterface;
use Drupal\Driver\Capability\ContentCapabilityInterface;
use Drupal\Driver\Capability\CronCapabilityInterface;
use Drupal\Driver\Capability\LanguageCapabilityInterface;
use Drupal\Driver\Capability\MailCapabilityInterface;
use Drupal\Driver\Capability\ModuleCapabilityInterface;
use Drupal\Driver\Capability\RoleCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Capability\WatchdogCapabilityInterface;
use Drupal\Driver\Core\CoreInterface;

/**
 * Contract for the full-featured Drupal driver.
 *
 * Bootstraps Drupal in-process and supports every capability.
 */
interface DrupalDriverInterface extends
  DriverInterface,
  SubDriverFinderInterface,
  AuthenticationCapabilityInterface,
  BlockCapabilityInterface,
  CacheCapabilityInterface,
  ConfigCapabilityInterface,
  ContentCapabilityInterface,
  CronCapabilityInterface,
  LanguageCapabilityInterface,
  MailCapabilityInterface,
  ModuleCapabilityInterface,
  RoleCapabilityInterface,
  UserCapabilityInterface,
  WatchdogCapabilityInterface {

  /**
   * Return current core.
   */
  public function getCore(): CoreInterface;

  /**
   * Injects the active Core implementation.
   *
   * Consumers override the driver's default Core lookup by passing any
   * class that implements 'CoreInterface' - the class name and namespace
   * do not matter. Typically called in a test bootstrap when the project
   * ships its own Core subclass (e.g. one that registers additional field
   * handlers in its 'registerDefaultFieldHandlers()' override).
   *
   * @param \Drupal\Driver\Core\CoreInterface $core
   *   The Core instance the driver should delegate to.
   */
  public function setCore(CoreInterface $core): void;

  /**
   * Determine major Drupal version.
   *
   * @return int
   *   The major Drupal version.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when the Drupal version could not be determined.
   *
   * @see drush_drupal_version()
   */
  public function getDrupalVersion(): int;

}
