<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal8\AbstractFieldHandler.
 */

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
  protected $fieldInfo = array();

  /**
   * Field configuration definition.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $fieldConfig = array();

  /**
   * Constructs an AbstractHandler object.
   *
   * @param \stdClass $entity
   *   The simulated entity object containing field information.
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   *
   * @throws \Exception
   */
  public function __construct(\stdClass $entity, $entity_type, $field_name) {
    $fields = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    $this->fieldInfo = $fields[$field_name];

    $bundle_key = \Drupal::entityManager()->getDefinition($entity_type)->getKey('bundle');
    if (empty($entity->{$bundle_key})) {
      throw new \Exception(sprintf('Invalid %s entity bundle key "%s" not set.', $entity_type, $bundle_key));
    }
    $bundle = $entity->{$bundle_key};

    $fields = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);
    if (empty($fields[$field_name])) {
      throw new \Exception(sprintf('Invalid bundle "%s" on entity type "%s".', $bundle_key, $entity_type));
    }
    $this->fieldConfig = $fields[$field_name];
  }

}
