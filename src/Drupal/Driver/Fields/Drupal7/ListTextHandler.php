<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\ListTextHandler
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Class ListTextHandler
 * @package Drupal\Driver\Fields\Drupal7
 */
class ListTextHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values) {
    $return = array();
    $allowed_values = array_flip($this->field_info['settings']['allowed_values']);
    foreach ($values as $value) {
      $return[$this->language][] = array('value' => $allowed_values[$value]);
    }
    return $return;
  }
}
