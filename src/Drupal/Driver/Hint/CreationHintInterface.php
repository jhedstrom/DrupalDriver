<?php

declare(strict_types=1);

namespace Drupal\Driver\Hint;

/**
 * Base contract for a creation hint.
 *
 * A hint represents one ergonomic stub property that is not a real Drupal
 * field, together with the resolution behaviour the driver applies during
 * entity creation. Concrete hints implement either
 * 'PreCreateHintInterface' (to mutate the stub before save) or
 * 'PostCreateHintInterface' (to act on the entity after save).
 */
interface CreationHintInterface {

  /**
   * Returns the hint name as it appears on entity stubs.
   *
   * @return string
   *   The stub property name this hint resolves (e.g. 'author', 'roles').
   */
  public function getName(): string;

  /**
   * Returns the Drupal entity type id this hint applies to.
   *
   * @return string
   *   A Drupal entity type id (e.g. 'node', 'user', 'taxonomy_term').
   */
  public function getEntityType(): string;

  /**
   * Returns a human-readable description of what this hint does.
   *
   * Used by documentation tools and error messages. Should describe the
   * input shape, the resolution behaviour, and the resulting effect on
   * the created entity in a single sentence.
   *
   * @return string
   *   A single-sentence description of the hint's behaviour.
   */
  public function getDescription(): string;

}
