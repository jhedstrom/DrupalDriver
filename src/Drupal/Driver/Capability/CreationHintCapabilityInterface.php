<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: expose the entity creation hints the driver understands.
 *
 * Creation hints are ergonomic property aliases on entity stubs that are
 * not real Drupal fields - for example, 'author' on a node stub that the
 * driver translates to a 'uid' value during creation. This capability
 * lets consumers discover which aliases are accepted, for what entity
 * type, and how to document them.
 *
 * This interface is intentionally NOT extended by the composite driver
 * interfaces. Consumers should check '$driver instanceof
 * CreationHintCapabilityInterface' before calling 'getCreationHints()'
 * so that hand-rolled drivers and test doubles that target the older
 * composite interfaces remain valid.
 */
interface CreationHintCapabilityInterface {

  /**
   * Returns creation hints that apply to the given entity type.
   *
   * @param string $entity_type
   *   The Drupal entity type id (e.g. 'node', 'user', 'taxonomy_term').
   *
   * @return array<string, \Drupal\Driver\Hint\CreationHintInterface>
   *   Map of hint name to hint instance. Empty array when no hints apply.
   */
  public function getCreationHints(string $entity_type): array;

}
