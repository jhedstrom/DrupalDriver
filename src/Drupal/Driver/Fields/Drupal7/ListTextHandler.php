<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\ListTextHandler.
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * ListText field handler for Drupal 7.
 */
class ListTextHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    $options = $this->fieldInfo['settings']['allowed_values'];
    if (!empty($this->fieldInfo['settings']['allowed_values_function'])) {
      $cacheable = TRUE;
      $callback = $this->fieldInfo['settings']['allowed_values_function'];
      $options = call_user_func($callback, $this->fieldInfo, $this, $this->entityType, $this->entity, $cacheable);
    }
    $allowed_values = array_flip($options);
    foreach ($values as $value) {
      $return[$this->language][] = array('value' => $allowed_values[$value]);
    }
    return $return;
  }

}