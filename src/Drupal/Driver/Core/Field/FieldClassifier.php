<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
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
   * {@inheritdoc}
   */
  public function fieldDefaultExpandReason(FieldStorageDefinitionInterface $storage): ?string {
    foreach ($storage->getPropertyDefinitions() as $name => $definition) {
      if ($definition->isComputed()) {
        continue;
      }

      if ($definition instanceof DataReferenceTargetDefinition) {
        return sprintf('property "%s" is an entity-reference target that must be resolved to an id', $name);
      }

      if ($definition instanceof ComplexDataDefinitionInterface || $definition instanceof ListDataDefinitionInterface) {
        return sprintf('property "%s" holds a complex or nested value', $name);
      }
    }

    return NULL;
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
