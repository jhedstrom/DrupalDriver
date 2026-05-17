<?php

declare(strict_types=1);

namespace Drupal\Driver\Alias;

/**
 * Base contract for a creation alias.
 *
 * An alias represents one ergonomic stub property that is not a real
 * Drupal field, together with the resolution behaviour the driver
 * applies during entity creation. Concrete aliases implement either
 * 'PreCreateAliasInterface' (to mutate the stub before save) or
 * 'PostCreateAliasInterface' (to act on the entity after save).
 */
interface CreationAliasInterface {

  /**
   * Returns the alias name as it appears on entity stubs.
   *
   * @return string
   *   The stub property name this alias resolves (e.g. 'author', 'roles').
   */
  public function getName(): string;

  /**
   * Returns the Drupal entity type id this alias applies to.
   *
   * @return string
   *   A Drupal entity type id (e.g. 'node', 'user', 'taxonomy_term').
   */
  public function getEntityType(): string;

  /**
   * Returns a human-readable description of what this alias does.
   *
   * Used by documentation tools and error messages. Should describe the
   * input shape, the resolution behaviour, and the resulting effect on
   * the created entity in a single sentence.
   *
   * @return string
   *   A single-sentence description of the alias's behaviour.
   */
  public function getDescription(): string;

}
