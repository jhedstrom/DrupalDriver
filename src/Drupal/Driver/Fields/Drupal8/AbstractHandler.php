<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal8\AbstractFieldHandler.
 */

namespace Drupal\Driver\Fields\Drupal8;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Base class for field handlers in Drupal 7.
 */
abstract class AbstractHandler implements FieldHandlerInterface {
  /**
   * Field storage definition.
   *
   * @var array
   */
  protected $fieldInfo = array();

  /**
   * Constructs an AbstractHandler object.
   *
   * @param \stdClass $entity
   *   The simulated entity object containing field information.
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   */
  public function __construct(\stdClass $entity, $entity_type, $field_name) {
    $fields = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    $this->fieldInfo = $fields[$field_name];
  }

}
