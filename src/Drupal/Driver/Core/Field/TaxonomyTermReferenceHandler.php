<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for taxonomy term references in Drupal 8.
 */
class TaxonomyTermReferenceHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $ids = [];

    foreach ($values as $name) {
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => $name]);

      if (!$terms) {
        throw new \Exception(sprintf("No term '%s' exists.", $name));
      }

      $ids[] = array_shift($terms)->id();
    }

    return $ids;
  }

}
