<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Default field handler for Drupal 8.
 */
class TextWithSummaryHandler implements FieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function expand(mixed $values): array {
    return (array) $values;
  }

}
