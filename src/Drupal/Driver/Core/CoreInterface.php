<?php

declare(strict_types=1);

namespace Drupal\Driver\Core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Component\Utility\Random;

/**
 * Drupal core interface.
 */
interface CoreInterface {

  /**
   * Instantiate the core interface.
   *
   * @param string $drupal_root
   *   The path to the Drupal root folder.
   * @param string $uri
   *   URI that is accessing Drupal. Defaults to 'default'.
   * @param \Drupal\Component\Utility\Random $random
   *   Random string generator.
   */
  public function __construct(string $drupal_root, string $uri = 'default', ?Random $random = NULL);

  /**
   * Return random generator.
   */
  public function getRandom(): Random;

  /**
   * Bootstrap Drupal.
   */
  public function bootstrap(): void;

  /**
   * Get module list.
   *
   * @return array<int, string>
   *   List of installed module names.
   */
  public function getModuleList(): array;

  /**
   * Returns a list of all extension absolute paths.
   *
   * @return array<string>
   *   An array of absolute paths to enabled extensions.
   */
  public function getExtensionPathList(): array;

  /**
   * Clear caches.
   */
  public function clearCache(): void;

  /**
   * Run cron.
   *
   * @return bool
   *   True if cron runs, otherwise false.
   */
  public function runCron(): bool;

  /**
   * Create a node.
   *
   * @param \stdClass $node
   *   The node object.
   *
   * @return object
   *   The created node.
   */
  public function nodeCreate(\stdClass $node): object;

  /**
   * Delete a node.
   *
   * @param \stdClass $node
   *   The node object.
   */
  public function nodeDelete(\stdClass $node): void;

  /**
   * Create a user.
   */
  public function userCreate(\stdClass $user): void;

  /**
   * Delete a user.
   */
  public function userDelete(\stdClass $user): void;

  /**
   * Add a role to a user.
   *
   * @param \stdClass $user
   *   The Drupal user object.
   * @param string $role_name
   *   The role name.
   */
  public function userAddRole(\stdClass $user, string $role_name): void;

  /**
   * Validate, and prepare environment for Drupal bootstrap.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when the Drupal site cannot be bootstrapped.
   *
   * @see _drush_bootstrap_drupal_site_validate()
   */
  public function validateDrupalSite(): void;

  /**
   * Processes a batch of actions.
   */
  public function processBatch(): void;

  /**
   * Create a taxonomy term.
   *
   * @return object
   *   The created term object.
   */
  public function termCreate(\stdClass $term): object;

  /**
   * Deletes a taxonomy term.
   *
   * @return bool
   *   Status constant indicating deletion.
   */
  public function termDelete(\stdClass $term): bool;

  /**
   * Creates a role.
   *
   * @param array<string> $permissions
   *   An array of permissions to create the role with.
   *
   * @return int|string
   *   The created role name.
   */
  public function roleCreate(array $permissions): int|string;

  /**
   * Deletes a role.
   *
   * @param string $role_name
   *   A role name to delete.
   */
  public function roleDelete(string $role_name): void;

  /**
   * Get FieldHandler class.
   *
   * @param object $entity
   *   The entity object.
   * @param string $entity_type
   *   Entity type machine name.
   * @param string $field_name
   *   Field machine name.
   *
   * @return \Drupal\Driver\Core\Field\FieldHandlerInterface
   *   The field handler.
   */
  public function getFieldHandler(object $entity, string $entity_type, string $field_name): FieldHandlerInterface;

  /**
   * Check if the specified field is an actual Drupal field.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $field_name
   *   The field name to check.
   *
   * @return bool
   *   TRUE if the given field is a Drupal field, FALSE otherwise.
   */
  public function isField(string $entity_type, string $field_name): bool;

  /**
   * Checks if the specified field is a Drupal base field.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $field_name
   *   The field name to check.
   *
   * @return bool
   *   TRUE if the given field is a base field, FALSE otherwise.
   */
  public function isBaseField(string $entity_type, string $field_name): bool;

  /**
   * Returns array of field types for the specified entity.
   *
   * @param string $entity_type
   *   The entity type for which to return the field types.
   * @param array<string> $base_fields
   *   Optional. Define base fields that will be returned in addition to user-
   *   defined fields.
   *
   * @return array<string, string>
   *   An associative array of field types, keyed by field name.
   */
  public function getEntityFieldTypes(string $entity_type, array $base_fields = []): array;

  /**
   * Creates a language.
   *
   * @param \stdClass $language
   *   An object with the following properties:
   *   - langcode: the langcode of the language to create.
   *
   * @return \stdClass|false
   *   The language object, or false if the language already exists.
   */
  public function languageCreate(\stdClass $language): \stdClass|false;

  /**
   * Deletes a language.
   *
   * @param \stdClass $language
   *   An object with the following properties:
   *   - langcode: the langcode of the language to delete.
   */
  public function languageDelete(\stdClass $language): void;

  /**
   * Clears the static caches.
   */
  public function clearStaticCaches(): void;

  /**
   * Returns a configuration item.
   *
   * @param string $name
   *   The name of the configuration object to retrieve.
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function configGet(string $name, string $key = ''): mixed;

  /**
   * Returns the original configuration item.
   *
   * @param string $name
   *   The name of the configuration object to retrieve.
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *
   * @return mixed
   *   The original data that was requested.
   */
  public function configGetOriginal(string $name, string $key = ''): mixed;

  /**
   * Sets a value in a configuration object.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param string $key
   *   Identifier to store value in configuration.
   * @param mixed $value
   *   Value to associate with identifier.
   */
  public function configSet(string $name, string $key, mixed $value): void;

  /**
   * Create an entity.
   *
   * @param string $entity_type
   *   Entity type machine name.
   * @param object $entity
   *   The field values and properties desired for the new entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity object.
   */
  public function entityCreate(string $entity_type, object $entity): EntityInterface;

  /**
   * Delete an entity.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param object $entity
   *   The entity to delete.
   */
  public function entityDelete(string $entity_type, object $entity): void;

  /**
   * Enable the test mail collector.
   */
  public function startCollectingMail(): void;

  /**
   * Restore normal operation of outgoing mail.
   */
  public function stopCollectingMail(): void;

  /**
   * Get any mail collected by the test mail collector.
   *
   * @return \stdClass[]
   *   An array of collected emails, each formatted as a Drupal 8
   *   \Drupal\Core\Mail\MailInterface::mail $message array.
   */
  public function getMail(): array;

  /**
   * Empty the test mail collector store of any collected mail.
   */
  public function clearMail(): void;

  /**
   * Send a mail.
   *
   * @param string $body
   *   The body of the mail.
   * @param string $subject
   *   The subject of the mail.
   * @param string $to
   *   The recipient's email address, passing PHP email validation filter.
   * @param string $langcode
   *   The language used in subject and body.
   *
   * @return bool
   *   Whether the email was sent successfully.
   */
  public function sendMail(string $body, string $subject, string $to, string $langcode): bool;

  /**
   * Installs a module.
   *
   * @param string $module_name
   *   The machine name of the module to install.
   */
  public function moduleInstall(string $module_name): void;

  /**
   * Uninstalls a module.
   *
   * @param string $module_name
   *   The machine name of the module to uninstall.
   */
  public function moduleUninstall(string $module_name): void;

}
