<?php

declare(strict_types=1);

namespace Drupal\Driver\Core;

use Drupal\Driver\Core\Field\DefaultHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
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
   * Returns the Drupal major version this Core targets.
   *
   * The default Core returns 0 - the lookup chain iterates only when version
   * is >= 10, so 0 skips the versioned directories and falls through to the
   * default handlers in Core\Field\.
   *
   * @return int
   *   The Drupal major version, or 0 for the default (no version-specific dir).
   */
  protected function getVersion(): int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldHandler($entity, $entity_type, $field_name): FieldHandlerInterface {
    $field_types = $this->getEntityFieldTypes($entity_type, [$field_name]);
    $camelized_type = Container::camelize($field_types[$field_name]);
    $version = $this->getVersion();

    $candidates = [];
    for ($n = $version; $n >= 10; $n--) {
      $candidates[] = sprintf('\\Drupal\\Driver\\Core%d\\Field\\%sHandler', $n, $camelized_type);
    }
    $candidates[] = sprintf('\\Drupal\\Driver\\Core\\Field\\%sHandler', $camelized_type);

    $default_candidates = [];
    for ($n = $version; $n >= 10; $n--) {
      $default_candidates[] = sprintf('\\Drupal\\Driver\\Core%d\\Field\\DefaultHandler', $n);
    }
    $default_candidates[] = DefaultHandler::class;

    foreach (array_merge($candidates, $default_candidates) as $class) {
      if (class_exists($class)) {
        return new $class($entity, $entity_type, $field_name);
      }
    }
    throw new \RuntimeException(sprintf('No field handler found for type "%s".', $camelized_type));
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
