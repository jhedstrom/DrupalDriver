<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\EntityreferenceHandler
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Class DefaultFieldHandler
 * @package Drupal\Driver\Fields\Drupal7
 */
class EntityreferenceHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand(array $values) {

    $entity_info = entity_get_info();
    $return = array();
    foreach ($values as $value) {

      $target_id = NULL;
      $referencable_entities = $this->field_info['foreign keys'];
      foreach ($referencable_entities as $entity => $settings) {
        if (isset($entity_info[$entity]['entity keys']['label'])) {
          $result = db_select($entity_info[$entity]['base table'], 't')
            ->fields('t', array($entity_info[$entity]['entity keys']['id']))
            ->condition('t.' . $entity_info[$entity]['entity keys']['label'], $value)
            ->execute()->fetchField();
          if ($result) {
            $target_id = $result;
          }
        }
      }
      if ($target_id) {
        $return[LANGUAGE_NONE][] = array('target_id' => $target_id);
      }
    }
    return $return;
  }
}
