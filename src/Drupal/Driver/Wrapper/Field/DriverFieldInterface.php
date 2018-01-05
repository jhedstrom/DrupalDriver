<?php

namespace Drupal\Driver\Wrapper\Field;

/**
 * Defines an interface for a Driver field wrapper that holds information about
 * a Drupal entity field.
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
  public function getDefinition();

  /**
   * Gets the particular field definition (D7 field instance definition, D8: field_storage_config).
   *
   * @return object|array
   *   the field definition.
   */
  public function getStorageDefinition();

  /**
   * Gets the particular field definition (D7 field instance definition, D8: field_storage_config).
   *
   * @return object|array
   *   the field definition.
   */
  public function getType();

  /**
   * Sets the raw values.
   *
   * @param array $values
   *   An array of unprocessed field value sets.
   */
  public function setRawValues(array $values);

  /**
   * Sets the processed values.
   *
   * @param array $values
   *   An array of processed field value sets.
   */
  public function setProcessedValues(array $values);

  /**
   * Sets the general field definition (D7 field definition, D8: field_config).
   *
   * @param array|object $definition
   *   A field definition (D7 field definition, D8: field_config).
   */
  public function setDefinition($definition);

  /**
   * Sets the particular field definition (D7 field instance definition, D8: field_storage_config).
   * @param array|object $definition
   *   A field storage definition (D7 field instance definition, D8: field_storage_config).
   */
  public function setStorageDefinition($definition);

}