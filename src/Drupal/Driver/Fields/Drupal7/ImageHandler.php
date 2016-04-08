<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Image field handler for Drupal 7.
 */
class ImageHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
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
