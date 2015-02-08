<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\AbstractFieldHandler
 */

namespace Drupal\Driver\Fields\Drupal7;

use Drupal\Driver\Fields\FieldHandlerInterface;

abstract class AbstractHandler implements FieldHandlerInterface {

  /**
   * @var
   */
  protected $field_info = array();

  /**
   * Get field instance information.
   *
   * @param $field_name
   * @return mixed
   */
  public function __construct($field_name) {
    $this->field_info = field_info_field($field_name);
  }

}
