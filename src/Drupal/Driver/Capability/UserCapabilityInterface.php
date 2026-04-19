<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: create, delete, and assign roles to users.
 */
interface UserCapabilityInterface {

  /**
   * Creates a user.
   *
   * @param \stdClass $user
   *   The user to create.
   */
  public function userCreate(\stdClass $user): void;

  /**
   * Deletes a user.
   *
   * @param \stdClass $user
   *   The user to delete.
   */
  public function userDelete(\stdClass $user): void;

  /**
   * Adds a role to a user.
   *
   * @param \stdClass $user
   *   The user to grant the role to.
   * @param string $role
   *   The role machine name or label.
   */
  public function userAddRole(\stdClass $user, string $role): void;

}
