<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal8\TextWithSummaryHandler.
 */

namespace Drupal\Driver\Fields\Drupal8;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Default field handler for Drupal 8.
 */
class TextWithSummaryHandler implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    return $values;
  }

}
