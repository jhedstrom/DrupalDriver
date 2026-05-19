<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'text_with_summary' fields.
 */
class TextWithSummaryHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    return $records;
  }

}
