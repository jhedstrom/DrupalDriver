<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\TaxonomyTermReferenceHandler
 */

namespace Drupal\Driver\Fields\Drupal8;
use Drupal\Tests\Core\Entity\EntityManagerTest;

/**
 * Class TaxonomyTermReferenceHandler
 * @package Drupal\Driver\Fields\Drupal8
 */
class TaxonomyTermReferenceHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values) {

    $return = array();
    foreach ($values as $name) {
      $terms = \Drupal::entityManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(array('name' => $name));
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
