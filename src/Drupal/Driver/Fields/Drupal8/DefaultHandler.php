<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\DefaultFieldHandler
 */

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Class DefaultFieldHandler
 * @package Drupal\Driver\Fields\Drupal8
 */
class DefaultHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values) {
    return $values;
  }
}
