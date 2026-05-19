<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'list_float' fields.
 */
class ListFloatHandler extends ListHandlerBase {

  /**
   * {@inheritdoc}
   */
  protected function castStorageKey(mixed $key): float {
    return (float) $key;
  }

}
