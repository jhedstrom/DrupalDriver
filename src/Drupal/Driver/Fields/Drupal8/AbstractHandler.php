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
  public function __construct($entity_type, $field_name) {
    $fields = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    $this->field_info = $fields[$field_name];
  }

}
