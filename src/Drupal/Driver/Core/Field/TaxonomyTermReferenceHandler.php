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
    $return = [];
    foreach ($values as $name) {
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => $name]);
      if ($terms) {
        $return[] = array_shift($terms)->id();
      }
      else {
        throw new \Exception(sprintf("No term '%s' exists.", $name));
      }
    }
    return $return;
  }

}
