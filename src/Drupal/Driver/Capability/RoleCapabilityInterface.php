<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: create and delete roles.
 */
interface RoleCapabilityInterface {

  /**
   * Creates a role with the given permissions.
   *
   * @param array<string> $permissions
   *   Permission machine names or labels.
   *
   * @return int|string
   *   The created role's machine name.
   */
  public function roleCreate(array $permissions): int|string;

  /**
   * Deletes a role.
   *
   * @param string $role_name
   *   The role machine name to delete.
   */
  public function roleDelete(string $role_name): void;

}
