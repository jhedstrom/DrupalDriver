<?php

namespace Drupal\Driver\Fields\Drupal7;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Node reference field handler for Drupal 7.
 */
class NodereferenceHandler implements FieldHandlerInterface {
  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $entity_type = 'node';
    $entity_info = entity_get_info($entity_type);
    $return = array();
    foreach ($values as $value) {
      $nid = db_select($entity_info['base table'], 't')
        ->fields('t', array($entity_info['entity keys']['id']))
        ->condition('t.' . $entity_info['entity keys']['label'], $value)
        ->execute()->fetchField();
      if ($nid) {
        $return[$this->language][] = array('nid' => $nid);
      }
    }

    return $return;
  }
}
