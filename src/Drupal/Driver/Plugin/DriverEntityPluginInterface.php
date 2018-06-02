<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for the Driver's entity plugins.
 */
interface DriverEntityPluginInterface extends PluginInspectionInterface {

  /**
   * Get the bundle key for the entity type.
   *
   * @return string
   *   The bundle key for the entity type
   */
  public function getBundleKey();

  /**
   * Get the label for the bundle key field for the entity type.
   *
   * @return array
   *   An array of (string) bundle key labels.
   */
  public function getBundleKeyLabels();

  /**
   * Gets the bundles for the current entity type.
   *
   * @return array
   *   An array of bundle machine names.
   */
  public function getBundles();

  /**
   * Get the machine names of fields that can be used as this entity's label.
   *
   * @return array
   *   An array of field instance machine names.
   */
  public function getLabelKeys();

  /**
   * Load an entity by its id.
   *
   * @param int|string $entityId
   *   An entity id.
   */
  public function load($entityId);

  /**
   * Set fields on the wrapped entity.
   *
   * @param array $fields
   *   An array of field values or driver field objects, keyed by identifier.
   *
   * @return $this
   */
  public function setFields(array $fields);

  /**
   * Whether the current entity type supports bundles.
   *
   * @return bool
   *   Whether the entity type supports bundles.
   */
  public function supportsBundles();

}
