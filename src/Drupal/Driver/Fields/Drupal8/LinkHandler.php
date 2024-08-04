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
      // 'options' is required to be an array, otherwise the utility class
      // Drupal\Core\Utility\UnroutedUrlAssembler::assemble() will complain.
      $options = [];
      // Instantiate the value as URI-only if $value is a string.
      if (is_string($value)) {
        $value = [NULL, $value];
      }
      if (!empty($value[2])) {
        parse_str($value[2], $options);
      }
      $return[] = array_filter([
        'options' => $options,
        'title' => $value[0],
        'uri' => $value[1],
      ], fn ($value) => !is_null($value));
    }
    return $return;
  }

}
