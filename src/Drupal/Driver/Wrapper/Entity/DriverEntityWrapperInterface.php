<?php

namespace Drupal\Driver\Wrapper\Entity;

/**
 * Defines an interface for a Driver entity wrapper that holds information
 * about a Drupal entity.
 */
interface DriverEntityWrapperInterface extends DriverEntityInterface {

  /**
   * Gets the machine name of the entity bundle.
   *
   * @return string
   *   the machine name of the entity bundle.
   */
  public function bundle();

  /**
   * Create and save an entity with certain field values.
   *
   * @param $fields
   *   An array of inputs that can be transformed into fields. These should
   *   each be either \Drupal\Driver\Wrapper\Field\DriverFieldInterface or an
   *   array of value sets (in which case the key of $fields must be the field
   *   name.
   * @param string $type
   *   A string identifying the entity type.
   * @param string $bundle
   *   (optional) A string identifying the entity bundle. Can be empty.
   *
   * @return $this
   */
  public static function create($fields, $type, $bundle);

  /**
   * Gets the machine name of the entity type.
   *
   * @return string
   *   the machine name of the entity type.
   */
  public function getEntityTypeId();

  /**
   * Gets the matching entity plugin.
   *
   * @return \Drupal\Driver\Plugin\DriverEntityPluginInterface $plugin
   *   An instantiated driver entity plugin matching this entity.
   */
  public function getFinalPlugin();

  /**
   * Load an entity by its id.
   *
   * @param integer|string $entityId
   *   An entity id.
   *
   * @return $this
   */
  public function load($entityId);

  /**
   * Sets the entity bundle.
   *
   * @param string $identifier
   *   A string identifying the entity bundle.
   *
   * @return $this
   */
  public function setBundle($identifer);

  /**
   * Set field values on the driver entity.
   *
   * @param $fields
   *   An array of inputs that can be transformed into fields. These should
   *   each be either \Drupal\Driver\Wrapper\Field\DriverFieldInterface or an
   *   array of value sets (in which case the key of $fields must be the field
   *   name.
   *
   * @return $this
   */
  public function setFields($fields);

  /**
   * Sets the matching entity plugin.
   *
   * @param \Drupal\Driver\Plugin\DriverEntityPluginInterface $plugin
   *   An instantiated driver entity plugin matching this entity.
   */
  public function setFinalPlugin($plugin);

}