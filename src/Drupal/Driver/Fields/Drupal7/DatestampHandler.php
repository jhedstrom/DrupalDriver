<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Datestamp field handler for Drupal 7.
 */
class DatestampHandler extends AbstractDateHandler {

  /**
   * {@inheritdoc}
   */
  protected function getDateFormat() {
    return 'U';
  }

}
