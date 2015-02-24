<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\DatetimeHandler
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Class DatetimeHandler
 * @package Drupal\Driver\Fields\Drupal7
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values) {
    $return = array();
    if (isset($this->field_info['columns']['value2'])) {
      foreach ($values as $value) {
        $return[$this->language][] = array(
          'value' => $value[0],
          'value2' => $value[1],
        );
      }
    }
    else {
      foreach ($values as $value) {
        $return[$this->language][] = array('value' => $value);
      }
    }
    return $return;
  }
}
