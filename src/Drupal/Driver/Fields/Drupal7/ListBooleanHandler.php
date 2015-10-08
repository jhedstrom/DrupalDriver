<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\ListBooleanHandler.
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * ListBoolean field handler for Drupal 7.
 */
class ListBooleanHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    if (!empty($this->fieldInfo['settings']['allowed_values_function'])) {
      $allowed_values = $this->fieldInfo['settings']['allowed_values_function']();
    }
    else {
      $allowed_values = $this->fieldInfo['settings']['allowed_values'];
    }
    // If values are blank then use keys as value.
    foreach ($allowed_values as $key => $value) {
      if ($value == '') {
        $allowed_values[$key] = $key;
      }
    }
    $allowed_values_flip = array_flip($allowed_values);
    foreach ($values as $value) {
      if (isset($allowed_values_flip[$value])) {
        $return[$this->language][] = array('value' => $allowed_values_flip[$value]);
      }
      else {
        $return[$this->language][] = array('value' => $value);
      }
    }
    return $return;
  }

}
