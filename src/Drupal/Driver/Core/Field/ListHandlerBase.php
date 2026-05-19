<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Base class for 'list_*' field handlers.
 */
abstract class ListHandlerBase extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $allowed_values = $this->fieldInfo->getSetting('allowed_values');
    $return = [];

    foreach ($records as $record) {
      $value = $record['value'];
      $key = array_search($value, $allowed_values, TRUE);
      $return[] = $key !== FALSE ? $key : $value;
    }

    return $return;
  }

}
