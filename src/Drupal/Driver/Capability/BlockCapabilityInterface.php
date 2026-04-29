<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Capability: place blocks and create content blocks.
 *
 * Groups the two distinct block operations a driver typically needs during
 * scenario setup:
 *
 *  - Placing a block in a region - the 'block' config entity. Answers what
 *    a scenario step like "Given the X block is placed in the Y region"
 *    resolves to internally.
 *  - Creating a content block - the 'block_content' content entity. The
 *    reusable block body that would normally be authored through the
 *    block library UI.
 *
 * Block type ('block_content_type') creation is intentionally out of scope.
 * Like node types and taxonomy vocabularies, it is schema-level setup that
 * belongs in the test site's configuration, not in a scenario step.
 */
interface BlockCapabilityInterface {

  /**
   * Places a block in a region.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   Block placement stub. Values mirror the 'block' config entity's keys:
   *   'id' (machine name of the placement; auto-generated when absent),
   *   'plugin' (block plugin id, e.g. 'system_powered_by_block' or
   *   'block_content:<uuid>'), 'theme', 'region', 'weight', 'settings',
   *   'visibility', 'status'.
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface
   *   The same stub, now flagged as saved with the placement attached. The
   *   resolved id is also written back onto the stub's values bag under
   *   the 'id' key.
   */
  public function blockPlace(EntityStubInterface $stub): EntityStubInterface;

  /**
   * Removes a placed block.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub returned by 'blockPlace()', or one that carries an 'id'
   *   value resolving to an existing block placement.
   */
  public function blockDelete(EntityStubInterface $stub): void;

  /**
   * Creates a content block.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   Content block stub. The 'bundle' property selects the block-content
   *   bundle; values map to fields on that bundle ('info', 'body', custom
   *   fields, etc.).
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface
   *   The same stub, now flagged as saved with the saved 'block_content'
   *   entity attached.
   */
  public function blockContentCreate(EntityStubInterface $stub): EntityStubInterface;

  /**
   * Deletes a content block.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub returned by 'blockContentCreate()', or one that carries
   *   the entity type's id key resolving to an existing content block.
   */
  public function blockContentDelete(EntityStubInterface $stub): void;

}
