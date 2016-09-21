<?php

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
    $allowed_values = array();
    if (!empty($this->fieldInfo['settings']['allowed_values_function'])) {
      $cacheable = TRUE;
      $callback = $this->fieldInfo['settings']['allowed_values_function'];
      $fn_allowed_values = call_user_func($callback, $this->fieldInfo, $this, $this->entityType, $this->entity, $cacheable);
      $options = array_flip($fn_allowed_values);
    }
    else {
      $options = array_flip($this->fieldInfo['settings']['allowed_values']);
    }
    foreach ($values as $value) {
      if (array_key_exists($value, $options)) {
        $allowed_values[$value] = $options[$value];
      }
      else {
        $allowed_values[$value] = $value;
      }
    }
    foreach ($values as $value) {
      $return[$this->language][] = array('value' => $allowed_values[$value]);
    }
    return $return;
  }

}
