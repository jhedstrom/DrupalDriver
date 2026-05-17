<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: expose the entity creation aliases the driver understands.
 *
 * Creation aliases are ergonomic property names on entity stubs that
 * are not real Drupal fields - for example, 'author' on a node stub
 * that the driver translates to a 'uid' value during creation. This
 * capability lets consumers discover which aliases are accepted, for
 * what entity type, and how to document them.
 *
 * This interface is intentionally NOT extended by the composite driver
 * interfaces. Consumers should check '$driver instanceof
 * CreationAliasCapabilityInterface' before calling
 * 'getCreationAliases()' so that hand-rolled drivers and test doubles
 * that target the older composite interfaces remain valid.
 */
interface CreationAliasCapabilityInterface {

  /**
   * Returns creation aliases that apply to the given entity type.
   *
   * @param string $entity_type
   *   The Drupal entity type id (e.g. 'node', 'user', 'taxonomy_term').
   *
   * @return array<string, \Drupal\Driver\Alias\CreationAliasInterface>
   *   Map of alias name to alias instance. Empty array when no aliases apply.
   */
  public function getCreationAliases(string $entity_type): array;

}
