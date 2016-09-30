<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Datetime field handler for Drupal 7.
 */
class DatetimeHandler extends AbstractDateHandler {

  /**
   * {@inheritdoc}
   */
  protected function getDateFormat() {
    return 'Y-m-d H:i:s';
  }

}
