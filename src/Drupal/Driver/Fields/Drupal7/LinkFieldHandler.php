<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\LinkFieldHandler
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Class LinkFieldHandler
 * @package Drupal\Driver\Fields\Drupal7
 */
class LinkFieldHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values, $language) {
    if (!$this->field_info['translatable']) {
      $language = LANGUAGE_NONE;
    }
    $return = array();
    foreach ($values as $value) {
      $return[$language][] = array(
        'title' => $value[0],
        'url' => $value[1],
      );
    }
    return $return;
  }
}
