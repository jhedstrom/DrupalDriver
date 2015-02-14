<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal8\AbstractFieldHandler
 */

namespace Drupal\Driver\Fields\Drupal8;

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
//    $manager = \Drupal::service('plugin.manager.field.field_type');
  }

}
