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
   * @param string|null $id
   *   Optional role machine name. If omitted, a random lowercase id is
   *   generated. Callers that need to reference the role by a known name
   *   (e.g. in assertions against a configuration form) should pass this.
   * @param string|null $label
   *   Optional human-readable role label. Defaults to the id when omitted;
   *   falls back to a random string only when both this and $id are NULL.
   *
   * @return string
   *   The created role's machine name.
   */
  public function roleCreate(array $permissions, ?string $id = NULL, ?string $label = NULL): string;

  /**
   * Deletes a role.
   *
   * @param string $role_name
   *   The role machine name to delete.
   */
  public function roleDelete(string $role_name): void;

}
