<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: create and delete content (nodes, terms, generic entities).
 */
interface ContentCapabilityInterface {

  /**
   * Creates a node.
   *
   * @param \stdClass $node
   *   The node stub.
   *
   * @return object
   *   The created node, with its identifier populated.
   */
  public function nodeCreate(\stdClass $node): object;

  /**
   * Deletes a node.
   *
   * @param object $node
   *   The node to delete.
   */
  public function nodeDelete(object $node): void;

  /**
   * Creates a taxonomy term.
   *
   * @param \stdClass $term
   *   The term stub.
   *
   * @return object
   *   The created term, with its identifier populated.
   */
  public function termCreate(\stdClass $term): object;

  /**
   * Deletes a taxonomy term.
   *
   * @param \stdClass $term
   *   The term to delete.
   *
   * @return bool
   *   TRUE when the term was deleted.
   */
  public function termDelete(\stdClass $term): bool;

  /**
   * Creates an entity of a given type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \stdClass $entity
   *   The entity stub.
   *
   * @return object
   *   The created entity.
   */
  public function entityCreate(string $entity_type, \stdClass $entity): object;

  /**
   * Deletes an entity of a given type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \stdClass $entity
   *   The entity to delete.
   */
  public function entityDelete(string $entity_type, \stdClass $entity): void;

}
