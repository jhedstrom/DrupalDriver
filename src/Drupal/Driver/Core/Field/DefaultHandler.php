<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Default field handler for Drupal 8.
 */
class DefaultHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand(mixed $values): array {
    return (array) $values;
  }

}
