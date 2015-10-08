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
    if (!empty($this->fieldInfo['settings']['allowed_values_function'])) {
      $allowed_values = $this->fieldInfo['settings']['allowed_values_function']();
    }
    else {
      $allowed_values = $this->fieldInfo['settings']['allowed_values'];
    }
    foreach ($values as $value) {
      $return[$this->language][] = array('value' => $allowed_values[$value]);
    }
    return $return;
  }

}
