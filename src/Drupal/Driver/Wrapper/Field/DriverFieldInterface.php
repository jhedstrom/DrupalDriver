<?php

namespace Drupal\Driver\Wrapper\Field;

/**
 * Defines an interface for the Driver's field wrappers.
 */
interface DriverFieldInterface {

  /**
   * Gets the machine name of the field.
   *
   * @return string
   *   the machine name of the field.
   */
  public function getName();

  /**
   * Gets the label of the field.
   *
   * @return string
   *   the label of the field.
   */
  public function getLabel();

  /**
   * Gets the raw values specified for the field.
   *
   * @return string
   *   the label of the field.
   */
  public function getRawValues();

  /**
   * Gets the values specified for the field given the processing so far.
   *
   * @return string
   *   the label of the field.
   */
  public function getProcessedValues();

  /**
   * Gets the general field definition (D7 field definition, D8: field_config).
   *
   * @return object|array
   *   the field definition.
   */
  public function getFieldDefinition();

  /**
   * Gets the particular field definition (D7 field instance definition, D8: field_storage_config).
   *
   * @return object|array
   *   the field definition.
   */
  public function getFieldStorageDefinition();

  /**
   * Sets the raw values.
   */
  public function setRawValues(array $values);

  /**
   * Sets the processed values.
   */
  public function setProcessedValues(array $values);

  /**
   * Sets the general field definition (D7 field definition, D8: field_config).
   */
  public function setFieldDefinition($definition);

  /**
   * Sets the particular field definition (D7 field instance definition, D8: field_storage_config).
   */
  public function setFieldWithStorageDefinition($definition);

}