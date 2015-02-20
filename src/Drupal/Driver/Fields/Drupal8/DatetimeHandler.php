<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal8\DatetimeHandler
 */

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Class DatetimeHandler
 * @package Drupal\Driver\Fields\Drupal8
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values) {
    foreach ($values as $key => $value) {
      $values[$key] = str_replace(' ', 'T', $value);
    }
    return $values;
  }
}
