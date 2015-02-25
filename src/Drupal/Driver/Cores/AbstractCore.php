<?php

/**
 * @file
 * Contains \Drupal\Driver\Cores\AbstractCore
 */

namespace Drupal\Driver\Cores;

use Symfony\Component\DependencyInjection\Container;

abstract class AbstractCore implements CoreInterface {

  /**
   * {@inheritDoc}
   */
  public function getFieldHandler($entity, $entity_type, $field_name) {

    $reflection = new \ReflectionClass($this);
    $core_namespace = $reflection->getShortName();
    $field_types = $this->getEntityFieldTypes($entity_type);
    $camelized_type = Container::camelize($field_types[$field_name]);
    $default_class = sprintf('\Drupal\Driver\Fields\%s\DefaultHandler', $core_namespace);
    $class_name = sprintf('\Drupal\Driver\Fields\%s\%sHandler', $core_namespace, $camelized_type);
    if (class_exists($class_name)) {
      return new $class_name($entity, $entity_type, $field_name);
    }
    return new $default_class($entity, $entity_type, $field_name);
  }

  /**
   * Given a entity, expand fields to match the format expected by entity_save().
   *
   * @param \stdClass $entity
   *   Entity object.
   * @return \stdClass
   *   Entity object.
   */
  protected function expandEntityFields($entity_type, \stdClass $entity) {
    $field_types = $this->getEntityFieldTypes($entity_type);
    foreach ($field_types as $field_name => $type) {
      if (isset($entity->$field_name)) {
        $entity->$field_name = $this->getFieldHandler($entity, $entity_type, $field_name)
          ->expand($entity->$field_name);
      }
    }
  }

}
