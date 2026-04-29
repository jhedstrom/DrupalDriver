<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Base class for field handlers.
 */
abstract class AbstractHandler implements FieldHandlerInterface {
  /**
   * Field storage definition.
   */
  protected FieldStorageDefinitionInterface $fieldInfo;

  /**
   * Field configuration definition.
   */
  protected FieldDefinitionInterface $fieldConfig;

  /**
   * Constructs an AbstractHandler object.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The simulated entity stub providing the bundle context.
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   *
   * @throws \Exception
   *   Thrown when the given field name does not exist on the entity.
   */
  public function __construct(EntityStubInterface $stub, string $entity_type, string $field_name) {
    if ($entity_type === '') {
      throw new \InvalidArgumentException('You must specify an entity type in order to parse entity fields.');
    }

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type);

    // Resolve the bundle: bundle key value > typed bundle > entity type
    // (single-bundle entities like 'user' use the entity type as the bundle).
    $bundle_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('bundle');
    $bundle = $entity_type;

    if ($bundle_key && !empty($stub->getValue($bundle_key))) {
      $bundle = (string) $stub->getValue($bundle_key);
    }
    elseif ($stub->getBundle() !== NULL && $stub->getBundle() !== '') {
      $bundle = $stub->getBundle();
    }

    $field_definitions = $entity_field_manager->getFieldDefinitions($entity_type, $bundle);

    if (empty($storage_definitions[$field_name]) || empty($field_definitions[$field_name])) {
      throw new \RuntimeException(sprintf('The field "%s" does not exist on entity type "%s" bundle "%s".', $field_name, $entity_type, $bundle));
    }

    $this->fieldInfo = $storage_definitions[$field_name];
    $this->fieldConfig = $field_definitions[$field_name];
  }

}
