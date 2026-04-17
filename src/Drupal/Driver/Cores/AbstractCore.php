<?php

declare(strict_types=1);

namespace Drupal\Driver\Cores;

use Drupal\Driver\Fields\FieldHandlerInterface;
use Drupal\Component\Utility\Random;
use Symfony\Component\DependencyInjection\Container;

/**
 * Base class for core drivers.
 */
abstract class AbstractCore implements CoreInterface {

  /**
   * System path to the Drupal installation.
   */
  protected string $drupalRoot;

  /**
   * URI for the Drupal installation.
   */
  protected string $uri;

  /**
   * Random generator.
   */
  protected Random $random;

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
  public function getRandom(): Random {
    return $this->random;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldHandler($entity, $entity_type, $field_name): FieldHandlerInterface {
    $reflection = new \ReflectionClass($this);
    $core_namespace = $reflection->getShortName();
    $field_types = $this->getEntityFieldTypes($entity_type, [$field_name]);
    $camelized_type = Container::camelize($field_types[$field_name]);
    $default_class = sprintf('\Drupal\Driver\Fields\%s\DefaultHandler', $core_namespace);
    $class_name = sprintf('\Drupal\Driver\Fields\%s\%sHandler', $core_namespace, $camelized_type);
    if (class_exists($class_name)) {
      return new $class_name($entity, $entity_type, $field_name);
    }
    return new $default_class($entity, $entity_type, $field_name);
  }

  /**
   * Expands properties on the given entity object to the expected structure.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \stdClass $entity
   *   Entity object.
   * @param array<string> $base_fields
   *   Optional. Define base fields that will be expanded in addition to user
   *   defined fields.
   */
  protected function expandEntityFields(string $entity_type, \stdClass $entity, array $base_fields = []): void {
    $field_types = $this->getEntityFieldTypes($entity_type, $base_fields);
    foreach (array_keys($field_types) as $field_name) {
      if (isset($entity->$field_name)) {
        $entity->$field_name = $this->getFieldHandler($entity, $entity_type, $field_name)
          ->expand($entity->$field_name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches(): void {
  }

}
