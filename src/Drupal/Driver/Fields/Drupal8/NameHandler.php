<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Name field handler for Drupal 8.
 *
 * Supports the Name module (https://www.drupal.org/project/name).
 */
class NameHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = [];
    $components = ['title', 'given', 'middle', 'family', 'generational', 'credentials'];

    foreach ($values as $value) {
      if (is_string($value)) {
        // Support "Family, Given" shorthand.
        $parts = array_map('trim', explode(',', $value));
        $return[] = [
          'family' => $parts[0] ?? NULL,
          'given' => $parts[1] ?? NULL,
        ];
        continue;
      }

      if (is_array($value)) {
        $return_value = [];
        $idx = 0;
        foreach ($value as $k => $v) {
          if (in_array($k, $components, TRUE)) {
            $return_value[$k] = $v;
          }
          elseif (is_numeric($k) && isset($components[$idx])) {
            $return_value[$components[$idx]] = $v;
            $idx++;
          }
        }
        $return[] = $return_value;
      }
    }

    return $return;
  }

}
