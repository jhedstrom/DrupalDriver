<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Link field handler for Drupal 8.
 */
class LinkHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return_values = [];
    foreach ($values as $value) {
      // 'options' is required to be an array, otherwise the utility class
      // Drupal\Core\Utility\UnroutedUrlAssembler::assemble() will complain.
      $return_value = [
        'title' => $value['title'] ?? $value[0] ?? NULL,
        'uri' => $value['uri'] ?? $value[1] ?? NULL,
        'options' => [],
      ];
      $options = $value['options'] ?? $value[2] ?? NULL;
      if ($options) {
        parse_str($options, $return_value['options']);
      }
      $return_values[] = $return_value;
    }
    return $return_values;
  }

}
