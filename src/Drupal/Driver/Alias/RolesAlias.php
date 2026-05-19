<?php

declare(strict_types=1);

namespace Drupal\Driver\Alias;

use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Entity\EntityStubInterface;
use Drupal\Driver\Exception\CreationAliasResolutionException;

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
class RolesAlias implements PostCreateAliasInterface {

  /**
   * Constructs the alias with the driver that receives role calls.
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
      // EntityReferenceHandler expands 'roles' into records like
      // '['target_id' => 'editor']'. Unwrap that here so the alias can
      // operate on the same role name the caller supplied originally.
      if (is_array($role) && array_key_exists('target_id', $role)) {
        $role = $role['target_id'];
      }

      if (!is_scalar($role) && !$role instanceof \Stringable) {
        throw new CreationAliasResolutionException("Cannot assign role because one of the 'roles' entries is not a scalar or stringable value.");
      }

      $name = trim((string) $role);

      if ($name === '') {
        throw new CreationAliasResolutionException("Cannot assign role because one of the 'roles' entries is empty after trimming.");
      }

      $this->driver->userAddRole($stub, $name);
    }
  }

}
