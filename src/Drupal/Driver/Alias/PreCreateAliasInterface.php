<?php

declare(strict_types=1);

namespace Drupal\Driver\Alias;

use Drupal\Driver\Entity\EntityStubInterface;

/**
 * A creation alias that mutates the stub before the entity is created.
 *
 * Implementations resolve the alias value, write any derived real-field
 * values back onto the stub, and remove the alias's own key from the
 * stub so the values bag passed to Drupal's entity factory contains
 * only real fields.
 */
interface PreCreateAliasInterface extends CreationAliasInterface {

  /**
   * Resolves the alias value and mutates the stub in place.
   *
   * Implementations MUST remove the alias's own value from the stub
   * (via 'EntityStubInterface::removeValue()') once resolution succeeds,
   * unless they intentionally overwrite the same key with the resolved
   * representation (e.g. swapping a term name for a tid).
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub being prepared for creation. Must already carry a value
   *   under 'getName()' - the dispatcher checks for presence first.
   *
   * @throws \Drupal\Driver\Exception\CreationAliasResolutionException
   *   When the value cannot be resolved into a Drupal storage value.
   */
  public function applyToStub(EntityStubInterface $stub): void;

}
