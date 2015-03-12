<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\TaxonomyTermReferenceHandler
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Class TaxonomyTermReferenceHandler
 * @package Drupal\Driver\Fields\Drupal7
 */
class TaxonomyTermReferenceHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values) {
    $return = array();
    foreach ($values as $name) {
      $terms = taxonomy_get_term_by_name($name);
      if (!$terms) {
        throw new \Exception(sprintf("No term '%s' exists.", $name));
      }
      $return[$this->language][] = array('tid' => array_shift($terms)->tid);
    }
    return $return;
  }
}
