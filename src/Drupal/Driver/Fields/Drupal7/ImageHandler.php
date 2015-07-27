<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\ImageHandler
 */

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Class ImageHandler
 * @package Drupal\Driver\Fields\Drupal7
 */
class ImageHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values) {
    $return = array();
    foreach ($values as $value) {
      $return[$this->language][] = array(
        'filename' => $value[0],
        'uri' => $value[1],
        'fid' => $value[2],
        'display' => isset($value[3]) ? $value[3] : 1,
      );
    }
    return $return;
  }
}
