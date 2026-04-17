<?php

declare(strict_types=1);

namespace Drupal\Driver\Fields\Drupal8;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Base class for field handlers in Drupal 8.
 */
abstract class AbstractHandler implements FieldHandlerInterface {
  /**
   * Field storage definition.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldInfo;

  /**
   * Field configuration definition.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $fieldConfig;

  /**
   * Constructs an AbstractHandler object.
   *
   * @param \StdClass $entity
   *   The simulated entity object containing field information.
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   *
   * @throws \Exception
   *   Thrown when the given field name does not exist on the entity.
   */
  public function __construct(\StdClass $entity, $entity_type, $field_name) {
    if (empty($entity_type)) {
      throw new \Exception("You must specify an entity type in order to parse entity fields.");
    }

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $fields = $entity_field_manager->getFieldStorageDefinitions($entity_type);
    $this->fieldInfo = $fields[$field_name];

    // Resolve the bundle: explicit bundle key > step_bundle > entity type
    // (single-bundle entities like 'user' use the entity type as bundle).
    $bundle_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('bundle');
    $bundle = empty($entity->$bundle_key) ? ($entity->step_bundle ?? $entity_type) : $entity->$bundle_key;

    $fields = $entity_field_manager->getFieldDefinitions($entity_type, $bundle);

    if (empty($fields[$field_name])) {
      throw new \Exception(sprintf('The field "%s" does not exist on entity type "%s" bundle "%s".', $field_name, $entity_type, $bundle));
    }
    $this->fieldConfig = $fields[$field_name];
  }

}
