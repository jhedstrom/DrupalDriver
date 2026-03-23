<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * CommerceProductReference field handler for Drupal 7.
 */
class CommerceProductReferenceHandler extends AbstractHandler {
  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $entity_type = 'commerce_product'; // $this->fieldInfo['settings']['target_type'];
    $entity_info = entity_get_info($entity_type);

    $return = array();
    foreach ($values as $value) {
      $product_id = db_select($entity_info['base table'], 't')
        ->fields('t', array($entity_info['entity keys']['id']))
        ->condition('t.' . $entity_info['entity keys']['label'], $value)
        ->execute()->fetchField();
      if ($product_id) {
        $return[$this->language][] = array('product_id' => $product_id);
      }
    }
    return $return;
  }
}
