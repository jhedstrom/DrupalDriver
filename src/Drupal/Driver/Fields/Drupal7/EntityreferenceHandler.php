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
    $entityType = $this->fieldInfo['settings']['target_type'];
    $entityInfo = entity_get_info($entityType);
    // For users set label to username.
    if ($entityType == 'user') {
      $entityInfo['entity keys']['label'] = 'name';
    }

    $return = [];
    foreach ($values as $value) {
      $targetId = db_select($entityInfo['base table'], 't')
        ->fields('t', [$entityInfo['entity keys']['id']])
        ->condition('t.' . $entityInfo['entity keys']['label'], $value)
        ->execute()->fetchField();
      if ($targetId) {
        $return[$this->language][] = ['target_id' => $targetId];
      }
    }
    return $return;
  }

}
