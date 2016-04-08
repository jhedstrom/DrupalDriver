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
    $allowed_values = array_flip($this->fieldInfo['settings']['allowed_values']);
    foreach ($values as $value) {
      $return[$this->language][] = array('value' => $allowed_values[$value]);
    }
    return $return;
  }

}
