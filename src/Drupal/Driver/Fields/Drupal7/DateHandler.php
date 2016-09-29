<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Date field handler for Drupal 7.
 */
class DateHandler extends AbstractDateHandler {

  /**
   * {@inheritdoc}
   */
  protected function getDateFormat() {
    return 'Y-m-d\Th:m:s';
  }

}
