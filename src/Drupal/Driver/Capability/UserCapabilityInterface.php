<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Capability: create, delete, and assign roles to users.
 */
interface UserCapabilityInterface {

  /**
   * Creates a user.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The user stub. The driver writes the resolved 'uid' back onto the
   *   stub and marks it saved with the created account.
   */
  public function userCreate(EntityStubInterface $stub): void;

  /**
   * Deletes a user.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub returned from a previous 'userCreate()' call, or one that
   *   carries a 'uid' value resolving to an existing user.
   */
  public function userDelete(EntityStubInterface $stub): void;

  /**
   * Adds a role to a user.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The user stub.
   * @param string $role
   *   The role machine name or label.
   */
  public function userAddRole(EntityStubInterface $stub, string $role): void;

}
