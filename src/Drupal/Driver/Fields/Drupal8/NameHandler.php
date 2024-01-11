<?php

namespace Drupal\Driver\Fields\Drupal8;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Default field handler for Drupal 8.
 */
class NameHandler implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $values = preg_split("/, /", $values);
    print_r($values);
    return [
      'family' => $values[0],
      'given' => $values[1],
    ];
  }
}
