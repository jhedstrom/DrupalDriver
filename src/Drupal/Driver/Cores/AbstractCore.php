<?php

namespace Drupal\Driver\Cores;

use Drupal\Component\Utility\Random;
use Symfony\Component\DependencyInjection\Container;

/**
 * Base class for core drivers.
 */
abstract class AbstractCore implements CoreInterface {

  /**
   * System path to the Drupal installation.
   *
   * @var string
   */
  protected $drupalRoot;

  /**
   * URI for the Drupal installation.
   *
   * @var string
   */
  protected $uri;

  /**
   * Random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $random;

  /**
   * {@inheritdoc}
   */
  public function __construct($drupal_root, $uri = 'default', ?Random $random = NULL) {
    $this->drupalRoot = realpath($drupal_root);
    $this->uri = $uri;
    if (!isset($random)) {
      $random = new Random();
    }
    $this->random = $random;
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom() {
    return $this->random;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldHandler($entity, $entity_type, $field_name) {
    $reflection = new \ReflectionClass($this);
    $coreNamespace = $reflection->getShortName();
    $fieldTypes = $this->getEntityFieldTypes($entity_type, [$field_name]);
    $camelizedType = Container::camelize($fieldTypes[$field_name]);
    $defaultClass = sprintf('\Drupal\Driver\Fields\%s\DefaultHandler', $coreNamespace);
    $className = sprintf('\Drupal\Driver\Fields\%s\%sHandler', $coreNamespace, $camelizedType);
    if (class_exists($className)) {
      return new $className($entity, $entity_type, $field_name);
    }
    return new $defaultClass($entity, $entity_type, $field_name);
  }

  /**
   * Expands properties on the given entity object to the expected structure.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param object $entity
   *   Entity object.
   * @param array $base_fields
   *   Optional. Define base fields that will be expanded in addition to user
   *   defined fields.
   */
  protected function expandEntityFields($entity_type, \stdClass $entity, array $base_fields = []) {
    $fieldTypes = $this->getEntityFieldTypes($entity_type, $base_fields);
    foreach ($fieldTypes as $fieldName => $type) {
      if (isset($entity->$fieldName)) {
        $entity->$fieldName = $this->getFieldHandler($entity, $entity_type, $fieldName)
          ->expand($entity->$fieldName);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
  }

}
