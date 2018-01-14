<?php

namespace Drupal\Driver\Wrapper\Field;

/**
 * Defines an interface for a Driver field wrapper that holds information about
 * a Drupal entity field.
 */
interface DriverFieldInterface
{

  /**
   * Gets the bundle context for this driver field.
   *
   * @return string
   *   Bundle machine name.
   */
    public function getBundle();

  /**
   * Gets the general field definition (D7 field definition, D8: field_config).
   *
   * @return object|array
   *   the field definition.
   */
    public function getDefinition();

  /**
   * Gets the entity type context for this driver field.
   *
   * @return string
   *   Entity type machine name.
   */
    public function getEntityType();

  /**
   * Gets the machine name of the field.
   *
   * @return string
   *   the machine name of the field.
   */
    public function getName();

  /**
   * Gets the values specified for the field given the processing so far.
   *
   * @return string
   *   the label of the field.
   */
    public function getProcessedValues();

  /**
   * Gets project plugin root.
   *
   * @return string
   *   Directory to search for additional project-specific driver plugins.
   */
    public function getProjectPluginRoot();

  /**
   * Gets the raw values specified for the field.
   *
   * @return string
   *   the label of the field.
   */
    public function getRawValues();

  /**
   * Gets the particular field definition (D7 field instance definition, D8: field_storage_config).
   *
   * @return object|array
   *   the field definition.
   */
    public function getStorageDefinition();

  /**
   * Get the type of this field.
   *
   * @return string
   *   Field type machine name.
   */
    public function getType();

  /**
   * Whether or not this field is a config property.
   *
   * @return boolean
   *   Whether this field is a config property.
   */
    public function isConfigProperty();
}
