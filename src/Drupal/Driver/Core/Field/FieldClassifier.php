<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Default Drupal 10/11 field classifier.
 *
 * See 'src/Drupal/Driver/Core/Field/README.md' for the full truth table.
 */
class FieldClassifier implements FieldClassifierInterface {

  /**
   * Constructs the classifier.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   */
  public function __construct(protected EntityFieldManagerInterface $entityFieldManager) {
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBaseStandard(string $entity_type, string $field_name): bool {
    $base = $this->entityFieldManager->getBaseFieldDefinitions($entity_type);

    if (!isset($base[$field_name])) {
      return FALSE;
    }

    $definition = $base[$field_name];

    return !$definition->isComputed() && !$definition->getFieldStorageDefinition()->hasCustomStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBaseComputedReadOnly(string $entity_type, string $field_name): bool {
    $base = $this->entityFieldManager->getBaseFieldDefinitions($entity_type);

    if (!isset($base[$field_name])) {
      return FALSE;
    }

    $definition = $base[$field_name];

    return $definition->isComputed() && $definition->isReadOnly();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBaseComputedWritable(string $entity_type, string $field_name): bool {
    $base = $this->entityFieldManager->getBaseFieldDefinitions($entity_type);

    if (!isset($base[$field_name])) {
      return FALSE;
    }

    $definition = $base[$field_name];

    return $definition->isComputed() && !$definition->isReadOnly();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBaseCustomStorage(string $entity_type, string $field_name): bool {
    $base = $this->entityFieldManager->getBaseFieldDefinitions($entity_type);

    if (!isset($base[$field_name])) {
      return FALSE;
    }

    $definition = $base[$field_name];

    return !$definition->isComputed() && $definition->getFieldStorageDefinition()->hasCustomStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsConfigurable(string $entity_type, string $field_name): bool {
    $storage = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);

    return isset($storage[$field_name]) && $storage[$field_name] instanceof FieldStorageConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBundleComputedReadOnly(string $entity_type, string $field_name, string $bundle): bool {
    if ($this->isBaseField($entity_type, $field_name)) {
      return FALSE;
    }

    $definition = $this->bundleFieldDefinition($entity_type, $field_name, $bundle);

    if (!$definition instanceof FieldDefinitionInterface) {
      return FALSE;
    }

    return $definition->isComputed() && $definition->isReadOnly();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBundleComputedWritable(string $entity_type, string $field_name, string $bundle): bool {
    if ($this->isBaseField($entity_type, $field_name)) {
      return FALSE;
    }

    $definition = $this->bundleFieldDefinition($entity_type, $field_name, $bundle);

    if (!$definition instanceof FieldDefinitionInterface) {
      return FALSE;
    }

    return $definition->isComputed() && !$definition->isReadOnly();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBundleCustomStorage(string $entity_type, string $field_name, string $bundle): bool {
    if ($this->isBaseField($entity_type, $field_name)) {
      return FALSE;
    }

    $definition = $this->bundleFieldDefinition($entity_type, $field_name, $bundle);

    if (!$definition instanceof FieldDefinitionInterface) {
      return FALSE;
    }

    return !$definition->isComputed() && $definition->getFieldStorageDefinition()->hasCustomStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBundleStorageBacked(string $entity_type, string $field_name, string $bundle): bool {
    $storage = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);

    if (!isset($storage[$field_name])) {
      return FALSE;
    }

    if ($storage[$field_name] instanceof FieldStorageConfig) {
      return FALSE;
    }

    if ($this->isBaseField($entity_type, $field_name)) {
      return FALSE;
    }

    $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    return isset($bundle_fields[$field_name]);
  }

  /**
   * Checks whether a field name is in the entity-type-wide base definitions.
   */
  protected function isBaseField(string $entity_type, string $field_name): bool {
    $base = $this->entityFieldManager->getBaseFieldDefinitions($entity_type);

    return isset($base[$field_name]);
  }

  /**
   * Returns the bundle-scoped definition for a field, or NULL if absent.
   */
  protected function bundleFieldDefinition(string $entity_type, string $field_name, string $bundle): ?FieldDefinitionInterface {
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    return $definitions[$field_name] ?? NULL;
  }

}
