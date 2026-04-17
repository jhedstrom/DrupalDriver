<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;

/**
 * Driver interface.
 */
interface DriverInterface {

  /**
   * Returns a random generator.
   */
  public function getRandom(): Random;

  /**
   * Bootstraps operations, as needed.
   */
  public function bootstrap(): void;

  /**
   * Determines if the driver has been bootstrapped.
   */
  public function isBootstrapped(): bool;

  /**
   * Creates a user.
   */
  public function userCreate(\stdClass $user): void;

  /**
   * Deletes a user.
   */
  public function userDelete(\stdClass $user): void;

  /**
   * Processes a batch of actions.
   */
  public function processBatch(): void;

  /**
   * Adds a role for a user.
   *
   * @param \stdClass $user
   *   A user object.
   * @param string $role
   *   The role name to assign.
   */
  public function userAddRole(\stdClass $user, string $role): void;

  /**
   * Retrieves watchdog entries.
   *
   * @param int $count
   *   Number of entries to retrieve.
   * @param string $type
   *   Filter by watchdog type.
   * @param string $severity
   *   Filter by watchdog severity level.
   *
   * @return string
   *   Watchdog output.
   */
  public function fetchWatchdog(int $count = 10, ?string $type = NULL, ?string $severity = NULL): string;

  /**
   * Clears Drupal caches.
   *
   * @param string $type
   *   Type of cache to clear defaults to all.
   */
  public function clearCache(?string $type = NULL): void;

  /**
   * Clears static Drupal caches.
   */
  public function clearStaticCaches(): void;

  /**
   * Creates a node.
   *
   * @param object $node
   *   Fully loaded node object.
   *
   * @return object
   *   The node object including the node ID in the case of new nodes.
   */
  public function createNode(object $node): object;

  /**
   * Deletes a node.
   *
   * @param object $node
   *   Fully loaded node object.
   */
  public function nodeDelete(object $node): void;

  /**
   * Runs cron.
   */
  public function runCron(): bool;

  /**
   * Creates a taxonomy term.
   *
   * @param \stdClass $term
   *   Term object.
   *
   * @return object
   *   The term object including the term ID in the case of new terms.
   */
  public function createTerm(\stdClass $term): object;

  /**
   * Deletes a taxonomy term.
   *
   * @param \stdClass $term
   *   Term object to delete.
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
   * @return string
   *   Role name of newly created role.
   */
  public function roleCreate(array $permissions): string;

  /**
   * Deletes a role.
   *
   * @param string $rid
   *   A role name to delete.
   */
  public function roleDelete(string $rid): void;

  /**
   * Check if the specified field is an actual Drupal field.
   *
   * @param string $entity_type
   *   The entity type to which the field should belong.
   * @param string $field_name
   *   The name of the field.
   *
   * @return bool
   *   TRUE if the field exists in the entity type, FALSE if not.
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
  public function configGet(string $name, string $key): mixed;

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
   * Creates an entity of a given type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \stdClass $entity
   *   The entity to create.
   *
   * @return object
   *   The created entity with `id` set.
   */
  public function createEntity(string $entity_type, \stdClass $entity): object;

  /**
   * Deletes an entity of a given type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \stdClass $entity
   *   The entity to delete.
   */
  public function entityDelete(string $entity_type, \stdClass $entity): void;

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
