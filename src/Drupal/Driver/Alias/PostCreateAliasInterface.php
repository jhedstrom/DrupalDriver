<?php

declare(strict_types=1);

namespace Drupal\Driver\Alias;

use Drupal\Driver\Entity\EntityStubInterface;

/**
 * A creation alias that acts on the entity after it has been saved.
 *
 * Use this lifecycle for side-effects that require the entity to exist
 * first - for example, assigning roles to a user after the user record
 * has been written, or attaching references to an entity that needs an
 * id before it can be linked.
 */
interface PostCreateAliasInterface extends CreationAliasInterface {

  /**
   * Reads the alias value from the stub and applies it to the entity.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub used to create the entity. The dispatcher checks that
   *   'getName()' is present on the stub before calling this method.
   * @param object $entity
   *   The Drupal entity that was just persisted.
   *
   * @throws \Drupal\Driver\Exception\CreationAliasResolutionException
   *   When the value cannot be applied.
   */
  public function applyAfterCreate(EntityStubInterface $stub, object $entity): void;

}
