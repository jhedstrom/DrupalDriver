<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal8\LinkHandler
 */

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Class LinkFieldHandler
 * @package Drupal\Driver\Fields\Drupal8
 */
class LinkHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values) {

    $return = array();
    foreach ($values as $value) {
      $return[] = array(
        // 'options' is required to be an array, otherwise the utility class
        // Drupal\Core\Utility\UnroutedUrlAssembler::assemble() will complain.
        'options' => array(),
        'title' => $value[0],
        'uri' => $value[1],
      );
    }
    return $return;
  }
}
