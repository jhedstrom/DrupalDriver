<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Daterange field handler for Drupal 8.
 */
class DaterangeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = [];
    foreach ($values as $value) {
      $start = str_replace(' ', 'T', $value[0]);
      $end = str_replace(' ', 'T', $value[1]);
      $return[] = [
        'value' => $start,
        'end_value' => $end,
      ];
    }
    return $return;
  }

}
