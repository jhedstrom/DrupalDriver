<?php

declare(strict_types=1);

namespace Drupal\Driver\Core;

use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Component\Utility\Random;

/**
 * Drupal-bootstrap contract.
 *
 * Covers the internals of bringing a Drupal site online and introspecting its
 * module and field configuration. Operational capabilities (users, content,
 * config, mail, and so on) live in the 'Drupal\Driver\Capability' namespace
 * and are implemented alongside this interface by the 'Core' class.
 */
interface CoreInterface {

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
