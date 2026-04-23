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

}
