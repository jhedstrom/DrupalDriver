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
      // Allow date ranges properties to be specified either explicitly,
      // or implicitly by array position.
      if (!isset($value['value']) && isset($value[0])) {
        $value['value'] = $value[0];
      }
      if (!isset($value['end_value'])) {
        if (isset($value[1])) {
          $value['end_value'] = $value[1];
        }
        else {
          // Allow end value to be optional (D#2794481).
          $value['end_value'] = NULL;
        }
      }
      $start = str_replace(' ', 'T', $value['value']);
      $end = str_replace(' ', 'T', $value['end_value']);
      $return[] = [
        'value' => $start,
        'end_value' => $end,
      ];
    }
    return $return;
  }

}
