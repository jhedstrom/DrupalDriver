<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

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
   * @param \stdClass $block
   *   Block placement stub. Properties mirror the 'block' config entity's
   *   keys: 'id' (machine name of the placement; auto-generated when
   *   absent), 'plugin' (block plugin id, e.g. 'system_powered_by_block'
   *   or 'block_content:<uuid>'), 'theme', 'region', 'weight', 'settings',
   *   'visibility', 'status'.
   *
   * @return object
   *   The saved 'block' config entity. The stub is mutated so that its
   *   'id' property holds the resolved placement id, matching the
   *   'nodeCreate'/'termCreate' mutation convention.
   */
  public function blockPlace(\stdClass $block): object;

  /**
   * Removes a placed block.
   *
   * @param object $block
   *   The block placement to delete. May be the stub returned by
   *   'blockPlace()' (needs an 'id' property) or a loaded 'block' entity.
   */
  public function blockDelete(object $block): void;

  /**
   * Creates a content block.
   *
   * @param \stdClass $block_content
   *   Content block stub. The 'type' property selects the block-content
   *   bundle; remaining properties map to fields on that bundle ('info',
   *   'body', custom fields, etc.).
   *
   * @return object
   *   The saved 'block_content' entity.
   */
  public function blockContentCreate(\stdClass $block_content): object;

  /**
   * Deletes a content block.
   *
   * @param object $block_content
   *   The content block to delete. May be the stub returned by
   *   'blockContentCreate()' or a loaded 'block_content' entity.
   */
  public function blockContentDelete(object $block_content): void;

}
