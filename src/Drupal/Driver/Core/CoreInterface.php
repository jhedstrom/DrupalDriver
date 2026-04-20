<?php

declare(strict_types=1);

namespace Drupal\Driver\Core;

use Drupal\Component\Utility\Random;
use Drupal\Driver\Capability\AuthenticationCapabilityInterface;
use Drupal\Driver\Capability\CacheCapabilityInterface;
use Drupal\Driver\Capability\ConfigCapabilityInterface;
use Drupal\Driver\Capability\ContentCapabilityInterface;
use Drupal\Driver\Capability\CronCapabilityInterface;
use Drupal\Driver\Capability\FieldCapabilityInterface;
use Drupal\Driver\Capability\LanguageCapabilityInterface;
use Drupal\Driver\Capability\MailCapabilityInterface;
use Drupal\Driver\Capability\ModuleCapabilityInterface;
use Drupal\Driver\Capability\RoleCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Capability\WatchdogCapabilityInterface;
use Drupal\Driver\Core\Field\FieldHandlerInterface;

/**
 * Contract for a Drupal-backed core implementation.
 *
 * Combines the Drupal-bootstrap internals (validate, module list, field
 * handler, and so on) with every operational capability. Used by
 * 'DrupalDriver' as the shared delegation target.
 */
interface CoreInterface extends
  AuthenticationCapabilityInterface,
  CacheCapabilityInterface,
  ConfigCapabilityInterface,
  ContentCapabilityInterface,
  CronCapabilityInterface,
  FieldCapabilityInterface,
  LanguageCapabilityInterface,
  MailCapabilityInterface,
  ModuleCapabilityInterface,
  RoleCapabilityInterface,
  UserCapabilityInterface,
  WatchdogCapabilityInterface {

  /**
   * Returns a random-value generator.
   */
  public function getRandom(): Random;

  /**
   * Boots Drupal in-process.
   */
  public function bootstrap(): void;

  /**
   * Validates the Drupal site and prepares the environment for bootstrap.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when the Drupal site cannot be bootstrapped.
   *
   * @see _drush_bootstrap_drupal_site_validate()
   */
  public function validateDrupalSite(): void;

  /**
   * Returns a list of installed module machine names.
   *
   * @return array<int, string>
   *   Installed module names.
   */
  public function getModuleList(): array;

  /**
   * Returns absolute paths for enabled extensions.
   *
   * @return array<string>
   *   Absolute paths to enabled extensions.
   */
  public function getExtensionPathList(): array;

  /**
   * Processes an outstanding Drupal batch, if any.
   */
  public function processBatch(): void;

  /**
   * Returns a field handler for the given entity/field.
   *
   * @param object $entity
   *   The entity being processed.
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field machine name.
   *
   * @return \Drupal\Driver\Core\Field\FieldHandlerInterface
   *   The matching field handler.
   */
  public function getFieldHandler(object $entity, string $entity_type, string $field_name): FieldHandlerInterface;

  /**
   * Registers a field handler class for a field type.
   *
   * Consumer projects call this to override one of the driver's built-in
   * handlers or to teach the driver about a field type it does not ship a
   * handler for. The registration wins over the defaults registered by
   * 'Core::registerDefaultFieldHandlers()' in the constructor. Handlers must
   * implement 'FieldHandlerInterface'; a class that does not triggers an
   * 'InvalidArgumentException' at registration time rather than at field
   * resolution time.
   *
   * @param string $field_type
   *   The Drupal field type id, e.g. 'boolean', 'entity_reference', or a
   *   project-specific id registered by a contrib module.
   * @param class-string<FieldHandlerInterface> $class
   *   The handler class to instantiate when a field of this type is
   *   expanded. The class must implement 'FieldHandlerInterface'.
   *
   * @throws \InvalidArgumentException
   *   When '$class' does not implement 'FieldHandlerInterface'.
   */
  public function registerFieldHandler(string $field_type, string $class): void;

  /**
   * Returns the field types for the given entity type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param array<string> $base_fields
   *   Optional. Base fields to include alongside user-defined fields.
   *
   * @return array<string, string>
   *   Map of field name to field type.
   */
  public function getEntityFieldTypes(string $entity_type, array $base_fields = []): array;

}
