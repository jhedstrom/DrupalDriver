<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Date field handler for Drupal 7.
 */
class DateHandler extends AbstractDateHandler {

  /**
   * {@inheritdoc}
   */
  protected $dateFormat = 'Y-m-d\TH:i:s';

}
