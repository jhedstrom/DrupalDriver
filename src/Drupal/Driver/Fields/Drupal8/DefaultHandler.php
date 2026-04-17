<?php

declare(strict_types=1);

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Default field handler for Drupal 8.
 */
class DefaultHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    return $values;
  }

}
