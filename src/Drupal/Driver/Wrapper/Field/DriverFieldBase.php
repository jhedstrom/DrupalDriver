<?php

namespace Drupal\Driver\Wrapper\Field;


/**
 * A base class for Driver field object that wraps a Drupal field object.
 */
abstract class DriverFieldBase implements DriverFieldInterface {

  /**
   * Field name.
   *
   * @var string
   */
  protected $name;

  /**
   * Entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * General field definition (D7 field definition, D8: field_config).
   *
   * @var object|array
   */
  protected $fieldDefinition;

  /**
   * Particular field definition (D7 field instance definition, D8: field_storage_config).
   *
   * @var object|array
   */
  protected $fieldStorageDefinition;

  /**
   * Raw field values before processing by DriverField plugins.
   *
   * @var array
   */
  protected $rawValues;

  /**
   * Field values after processing by DriverField plugins.
   *
   * @var array
   */
  protected $processedValues;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $rawValues, $fieldName, $entityType, $bundle = NULL) {
    foreach ($rawValues as $rawValue) {
      if (!is_array($rawValue)) {
        throw New \Exception("Every field value must be an associative array");
      }
    }

    if (is_null($bundle)) {
      $bundle = $entityType;
    }
    $this->setRawValues($rawValues);
    $this->setProcessedValues($rawValues);
    $this->setName($fieldName);
    $this->setEntityType($entityType);
    $this->setBundle($bundle);

    //field_info_field($entity_type, $field_name) -> FieldStorageConfig::loadByName($entity_type, $field_name)Only for cases where the code is explicitly working with configurable fields, see node_add_body_field() as an example.
    //field_info_instance($entity_type, $field_name, $bundle) -> FieldConfig::loadByName($entity_type, $bundle, $field_name).
    // the EntityManager provides the methods getFieldDefinitions($entity_type, $bundle) getFieldStorageDefinitions($entity_type)
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {

  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType() {
    return 'string';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawValues() {
    return $this->rawValues;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessedValues() {
    return $this->processedValues;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition() {
    return $this->fieldDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinition() {
    return $this->fieldStorageDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundle(string $bundle) {
    $this->bundle = $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setRawValues(array $values) {
    $this->rawValues = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessedValues(array $values) {
    $this->processedValues = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldDefinition($definition) {
    $this->fieldDefinition = $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldWithStorageDefinition($definition) {
    $this->fieldStorageDefinition = $definition;
  }

}