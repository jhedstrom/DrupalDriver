<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Alias;

use Drupal\Driver\Alias\PreCreateAliasInterface;
use Drupal\Driver\Entity\EntityStubInterface;
use Drupal\Driver\Exception\CreationAliasResolutionException;

/**
 * Resolves a parent term name on a term stub to the parent's 'tid'.
 *
 * Reads the value at 'parent', looks up the term within the stub's
 * vocabulary, and replaces the value in place with the resolved 'tid'.
 * No-ops when the value is empty. Throws when the parent term cannot
 * be found in the target vocabulary.
 *
 * The alias reads the target vocabulary from the stub's typed bundle
 * or from 'vid' (which the 'VocabularyMachineNameAlias' may have
 * populated earlier in the pre-create pipeline).
 */
class ParentTermAlias implements PreCreateAliasInterface {

  /**
   * Lookup callable for resolving a parent term name to a tid.
   *
   * Receives (parent name, vocabulary id); returns the matching parent
   * term id, or NULL when no match is found.
   *
   * @var \Closure(string, string): (int|string|null)
   */
  protected \Closure $parentLookup;

  /**
   * Constructs the alias.
   *
   * @param \Closure(string, string): (int|string|null)|null $parent_lookup
   *   Lookup callable. NULL uses a Drupal entity query against the
   *   'taxonomy_term' storage.
   */
  public function __construct(?\Closure $parent_lookup = NULL) {
    $this->parentLookup = $parent_lookup ?? static function (string $parent_name, string $vid): int|string|null {
      $tids = \Drupal::entityQuery('taxonomy_term')
        ->accessCheck(FALSE)
        ->condition('name', $parent_name)
        ->condition('vid', $vid)
        ->range(0, 2)
        ->execute();

      if (empty($tids)) {
        return NULL;
      }

      if (count($tids) > 1) {
        throw new CreationAliasResolutionException(sprintf("Cannot resolve parent term '%s' in vocabulary '%s' because multiple terms share that name.", $parent_name, $vid));
      }

      return reset($tids);
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'parent';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return "Resolves a parent term name supplied via 'parent' to the parent's 'tid'. The value is replaced in place. Throws when the parent term does not exist in the target vocabulary.";
  }

  /**
   * {@inheritdoc}
   */
  public function applyToStub(EntityStubInterface $stub): void {
    $parent_name = $stub->getValue('parent');

    if (empty($parent_name)) {
      return;
    }

    if (!is_scalar($parent_name) && !$parent_name instanceof \Stringable) {
      throw new CreationAliasResolutionException("Cannot resolve parent term because the 'parent' value is not a scalar or stringable object.");
    }

    $vid_raw = $stub->getBundle() ?? $stub->getValue('vid');

    if ($vid_raw !== NULL && !is_scalar($vid_raw) && !$vid_raw instanceof \Stringable) {
      throw new CreationAliasResolutionException("Cannot resolve parent term because the vocabulary value is not a scalar or stringable object.");
    }

    $vid = (string) $vid_raw;
    $parent_name = (string) $parent_name;

    if ($vid === '') {
      throw new CreationAliasResolutionException(sprintf("Cannot resolve parent term '%s' because the stub has no vocabulary.", $parent_name));
    }

    $tid = ($this->parentLookup)($parent_name, $vid);

    if ($tid === NULL) {
      throw new CreationAliasResolutionException(sprintf("Cannot create term because parent term '%s' does not exist in vocabulary '%s'.", $parent_name, $vid));
    }

    if (!is_numeric($tid) || (int) $tid <= 0) {
      throw new CreationAliasResolutionException(sprintf("Cannot resolve parent term '%s' in vocabulary '%s' because the lookup returned an invalid tid.", $parent_name, $vid));
    }

    // Cast to int so the downstream entity-reference handler treats the
    // value as a pre-resolved tid and skips its own validation query.
    $stub->setValue('parent', (int) $tid);
  }

}
