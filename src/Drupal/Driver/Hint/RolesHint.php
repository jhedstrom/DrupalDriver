<?php

declare(strict_types=1);

namespace Drupal\Driver\Hint;

use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Assigns roles to a user after the user has been created.
 *
 * Reads the 'roles' value (expected to be an array of role machine
 * names or labels) and calls 'userAddRole()' for each entry on the
 * driver supplied at construction. No-ops when the value is missing or
 * not an array.
 *
 * Shared across drivers - 'Core', 'DrupalDriver', and 'DrushDriver'
 * all register an instance that targets their own 'userAddRole()'
 * implementation.
 */
class RolesHint implements PostCreateHintInterface {

  /**
   * Constructs the hint with the driver that receives role calls.
   *
   * @param \Drupal\Driver\Capability\UserCapabilityInterface $driver
   *   The driver whose 'userAddRole()' will be called per role.
   */
  public function __construct(protected readonly UserCapabilityInterface $driver) {
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'roles';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'user';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return "Assigns roles to a user after creation. Accepts an array of role machine names or labels; ignores non-array values.";
  }

  /**
   * {@inheritdoc}
   */
  public function applyAfterCreate(EntityStubInterface $stub, object $entity): void {
    $roles = $stub->getValue('roles');

    if (!is_array($roles)) {
      return;
    }

    foreach ($roles as $role) {
      $this->driver->userAddRole($stub, (string) $role);
    }
  }

}
