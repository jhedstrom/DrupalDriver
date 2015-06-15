<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\DefaultFieldHandler.
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Default field handler for Drupal 7.
 */
class DefaultHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    foreach ($values as $value) {
      $return[$this->language][] = array('value' => $value);
    }
    return $return;
  }

}
