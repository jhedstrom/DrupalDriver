<?php

namespace Drupal\Driver\Wrapper\Field;

/**
 * A Driver field object that holds information about Drupal 8 field.
 */
class DriverFieldDrupal8 extends DriverFieldBase implements DriverFieldInterface {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getDefinition()->getType();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    // @todo implement for D7.
    //field_info_field($entity_type, $field_name) -> FieldStorageConfig::loadByName($entity_type, $field_name)Only for cases where the code is explicitly working with configurable fields, see node_add_body_field() as an example.
    //field_info_instance($entity_type, $field_name, $bundle) -> FieldConfig::loadByName($entity_type, $bundle, $field_name).
    // the EntityManager provides the methods getFieldDefinitions($entity_type, $bundle) getFieldStorageDefinitions($entity_type)

    if (is_null($this->definition)) {
      $entityFieldManager = \Drupal::service('entity_field.manager');
      $definitions = $entityFieldManager->getFieldDefinitions($this->getEntityType(), $this->getBundle());
      $this->setDefinition($definitions[$this->getName()]);
    }
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageDefinition() {
    return $this->getDefinition()->getFieldStorageDefinition();
  }
}