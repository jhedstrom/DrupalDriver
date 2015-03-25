<?php

namespace Drupal\Driver\Cores;

use Drupal\Component\Utility\Random;

/**
 * Drupal core interface.
 */
interface CoreInterface {

  /**
   * Instantiate the core interface.
   *
   * @param string $drupalRoot
   *
   * @param string $uri
   *   URI that is accessing Drupal. Defaults to 'default'.
   *
   * @param \Drupal\Component\Utility\Random $random
   *   Random string generator.
   */
  public function __construct($drupalRoot, $uri = 'default', Random $random = NULL);

  /**
   * Return random generator.
   */
  public function getRandom();

  /**
   * Bootstrap Drupal.
   */
  public function bootstrap();

  /**
   * Get module list.
   */
  public function getModuleList();

  /**
   * Clear caches.
   */
  public function clearCache();

  /**
   * Run cron.
   *
   * @return boolean
   *   True if cron runs, otherwise false.
   */
  public function runCron();

  /**
   * Create a node.
   */
  public function nodeCreate($node);

  /**
   * Delete a node.
   */
  public function nodeDelete($node);

  /**
   * Create a user.
   */
  public function userCreate(\stdClass $user);

  /**
   * Delete a user.
   */
  public function userDelete(\stdClass $user);

  /**
   * Add a role to a user.
   *
   * @param \stdClass $user
   *   The Drupal user object.
   * @param string
   *   The role name.
   */
  public function userAddRole(\stdClass $user, $role_name);

  /**
   * Validate, and prepare environment for Drupal bootstrap.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *
   * @see _drush_bootstrap_drupal_site_validate()
   */
  public function validateDrupalSite();

  public function processBatch();

  /**
   * Create a taxonomy term.
   */
  public function termCreate(\stdClass $term);

  /**
   * Delete a taxonomy term,
   */
  public function termDelete(\stdClass $term);

  /**
   * Create a role
   *
   * @param array $permissions
   *   An array of permissions to create the role with.
   *
   * @return integer
   *   The created role name.
   */
  public function roleCreate(array $permissions);

  /**
   * Delete a role
   *
   * @param string $role_name
   *   A role name to delete.
   */
  public function roleDelete($role_name);

  /**
   * Get FieldHandler class.
   *
   * @param $entity_type
   *    Entity type machine name.
   * @param $field_name
   *    Field machine name.
   * @return \Drupal\Driver\Fields\FieldHandlerInterface
   */
  public function getFieldHandler($entity, $entity_type, $field_name);

  /**
   * Check if the specified field is an actual Drupal field.
   *
   * @param $entity_type
   * @param $field_name
   * @return boolean
   */
  public function isField($entity_type, $field_name);

  /**
   * Return array of field types for the specified entity
   * keyed by their field names.
   *
   * @param $entity_type
   * @return array
   */
  public function getEntityFieldTypes($entity_type);

}
