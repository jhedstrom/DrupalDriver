<?php

namespace Drupal\Driver\Wrapper\Entity;

/**
 * Defines an interface shared by Driver entity wrappers & entity plugins.
 */
interface DriverEntityInterface
{

  /**
   * Delete the entity.
   *
   * @return $this
   */
    public function delete();

  /**
   * Gets the saved Drupal entity this object is wrapping for the driver.
   *
   * @return \Drupal\Core\Entity\EntityInterface;
   *   The Drupal entity being wrapped for the driver by this object.
   */
    public function getEntity();

  /**
   * Gets the id of this entity.
   *
   * @return string|integer
   *   The id of this entity.
   */
    public function id();

  /**
   * Whether the entity has been saved or is being newly constructed.
   *
   * @return boolean
   *   Whether or not a saved Drupal entity is attached.
   */
    public function isNew();

  /**
   * Gets the label of this entity.
   *
   * @return string
   *   The label of this entity.
   */
    public function label();

  /**
   * Reload the current entity from storage.
   *
   * @return $this
   */
    public function reload();

  /**
   * Save the entity.
   *
   * @return $this
   */
    public function save();


  /**
   * Set a field from text.
   *
   * @param string $identifier
   *   A string identifying an entity field.
   * @param object|string|array $field
   *   An driver field or something that can be transformed into one.
   *
   * @return $this
   */
    public function set($identifier, $field);

  /**
   * Reset Drupal as if this entity never existed.
   *
   * @return $this
   */
    public function tearDown();

  /**
   * Get the url of this entity.
   *
   * @param string $rel The link relationship type, for example: canonical or edit-form.
   * @param array $options See \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for the available options.
   *
   * @return string
   *   The url of this entity.
   */
    public function url($rel, $options);
}
