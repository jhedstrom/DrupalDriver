<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal7\AbstractFieldHandler
 */

namespace Drupal\Driver\Fields\Drupal7;

use Drupal\Driver\Fields\FieldHandlerInterface;

abstract class AbstractHandler implements FieldHandlerInterface {
  /**
   * @var string
   */
  protected $language = LANGUAGE_NONE;

  /**
   * @var
   */
  protected $field_info = array();

  /**
   * Get field instance information.
   *
   * @param $entity
   * @param $entity_type
   * @param $field_name
   * @return mixed
   */
  public function __construct($entity, $entity_type, $field_name) {
    $this->field_info = field_info_field($field_name);
    $this->language = field_is_translatable($entity_type, $this->field_info) ? entity_language($entity_type, $entity) : LANGUAGE_NONE;
  }
}
