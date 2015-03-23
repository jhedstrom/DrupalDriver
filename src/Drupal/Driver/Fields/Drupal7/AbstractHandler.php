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
  protected $language = NULL;
  protected $entity = NULL;
  protected $entity_type = NULL;
  protected $field_name = NULL;
  protected $field_info = array();

  /**
   * Get field instance information.
   *
   * @param $entity
   * @param $entity_type
   * @param $field_name
   * @return mixed
   */
  public function __construct(\stdClass $entity, $entity_type, $field_name) {
    $this->entity = $entity;
    $this->entity_type = $entity_type;
    $this->field_name = $field_name;
    $this->field_info = $this->getFieldInfo();
    $this->language = $this->getEntityLanguage();
  }

  /**
   * @param $method
   * @param $args
   * @return mixed
   */
  public function __call($method, $args) {
    if ($method == 'expand') {
      $args['values'] = (array) $args['values'];
    }
    return call_user_func_array(array($this, $method), $args);
  }

  /**
   * @return bool|mixed|void
   */
  public function getFieldInfo() {
    return field_info_field($this->field_name);
  }

  /**
   * @return null|string
   */
  public function getEntityLanguage() {
    if (field_is_translatable($this->entity_type, $this->field_info)) {
      return entity_language($this->entity_type, $this->entity);
    }
    else {
      return LANGUAGE_NONE;
    }
  }
}
