<?php

namespace Drupal\Driver\Fields\Drupal6;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Provides a custom field handler to make it easier to include taxonomy terms.
 */
class TaxonomyHandler implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $result = [];
    $values = (array) $values;
    foreach ($values as $entry) {
      $terms = explode(',', $entry);
      foreach ($terms as $term) {
        // Try to split things out in order to find optional specified vocabs.
        $termNameOrTid = '';
        $parts = explode(':', $term);
        if (count($parts) == 1) {
          $termNameOrTid = $term;
        }
        elseif (count($parts) == 2) {
          $termNameOrTid = $term;
        }
        if ($termList = taxonomy_get_term_by_name($termNameOrTid)) {
          $term = reset($termList);
          $result[] = $term;
        }
      }
    }

    return $result;
  }

}
