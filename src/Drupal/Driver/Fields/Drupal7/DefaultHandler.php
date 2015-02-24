<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\DefaultFieldHandler
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Class DefaultFieldHandler
 * @package Drupal\Driver\Fields\Drupal7
 */
class DefaultHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values, $language) {
    if (!$this->field_info['translatable']) {
      $language = LANGUAGE_NONE;
    }
    $return = array();
    foreach ($values as $value) {
      $return[$language][] = array('value' => $value);
    }
    return $return;
  }
}
