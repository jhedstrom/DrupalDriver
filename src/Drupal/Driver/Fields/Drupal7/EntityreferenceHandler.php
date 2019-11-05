<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Entityreference field handler for Drupal 7.
 */
class EntityreferenceHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $entity_type = $this->getEntityType();
    $entity_info = entity_get_info($entity_type);
    // For users set label to username.
    if ($entity_type == 'user') {
      $entity_info['entity keys']['label'] = 'name';
    }

    $return = array();
    foreach ($values as $value) {
      // Extract target id by step. Otherwise, try get entity searching
      // by entity label.
      if (is_array($value) && !empty($value['target_id']) && $this->entityExists($value['target_id'])) {
        $target_id = $value['target_id'];
      }
      else {
        $target_id = db_select($entity_info['base table'], 't')
          ->fields('t', array($entity_info['entity keys']['id']))
          ->condition('t.' . $entity_info['entity keys']['label'], $value)
          ->execute()->fetchField();
      }
      if ($target_id) {
        $return[$this->language][] = array('target_id' => $target_id);
      }
    }
    return $return;
  }

  /**
   * Get entity type.
   *
   * @return string
   *   Entity type (node, user, taxonomy, etc).
   */
  public function getEntityType() {
    return $this->fieldInfo['settings']['target_type'];
  }

  /**
   * Check target id belongs to a real entity.
   *
   * @param int $target_id
   *   Target id.
   *
   * @return bool
   *   Entity exists?
   */
  public function entityExists($target_id) {
    $entity_type = $this->getEntityType();
    $entity = entity_load_single($entity_type, $target_id);
    return !empty($entity);
  }

}
