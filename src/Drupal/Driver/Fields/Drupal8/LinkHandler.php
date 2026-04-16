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
    $return = [];
    foreach ($values as $value) {
      // Support both named keys (title, uri, options) and numeric indices.
      $return_value = [
        'title' => $value['title'] ?? $value[0] ?? NULL,
        'uri' => $value['uri'] ?? $value[1] ?? NULL,
        'options' => [],
      ];
      // 'options' is required to be an array, otherwise the utility class
      // Drupal\Core\Utility\UnroutedUrlAssembler::assemble() will complain.
      $options = $value['options'] ?? $value[2] ?? NULL;
      if ($options) {
        parse_str($options, $return_value['options']);
      }
      $return[] = $return_value;
    }
    return $return;
  }

}
