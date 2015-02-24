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
  public function expand($values,$language) {
    if (!$this->field_info['translatable']) {
      $language = LANGUAGE_NONE;
    }
    $return = array();
    if (isset($this->field_info['columns']['value2'])) {
      foreach ($values as $value) {
        $return[$language][] = array(
          'value' => $value[0],
          'value2' => $value[1],
        );
      }
    }
    else {
      foreach ($values as $value) {
        $return[$language][] = array('value' => $value);
      }
    }
    return $return;
  }
}
