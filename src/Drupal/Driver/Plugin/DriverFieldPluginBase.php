<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for Driver field plugins.
 */
class DriverFieldPluginBase extends PluginBase implements DriverFieldPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The field object this plugin is processing values for.
   *
   * @var \Drupal\Driver\Wrapper\Field\DriverFieldInterface
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->field = $configuration['field'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isFinal($field) {
    return $this->pluginDefinition['final'];
  }

  /**
   * {@inheritdoc}
   */
  public function processValues($values) {
    if (!is_array($values)) {
      throw new \Exception("Values must be an array");
    }
    $processed = [];
    foreach ($values as $value) {
      $value = $this->assignPropertyNames($value);
      $processed[] = $this->processValue($value);
    }
    return $processed;
  }

  /**
   * Converts a single instruction into an array of field properties for
   * content fields. We want to allow plugins to be called with field properties
   * already explicitly specified, but also need to allow for more cryptic
   * inputs that the plugin has to decipher.
   *
   * @return string|array
   *   returns the array of field properties for one field value.
   */
  protected function assignPropertyNames($value) {
    // Keep config properties simple.
    if ($this->field->isConfigProperty()) {}
    // Convert simple string
    elseif (!is_array($value)) {
      $value = [$this->getMainPropertyName() => $value];
    }
    // Convert single item unkeyed array.
    elseif (array_keys($value) === [0]) {
      $value = [$this->getMainPropertyName() => $value[0]];
    }
    return $value;
  }

  /**
   * Gets the default column name to use for field values.
   *
   * @return string
   *   The default column name for this field type.
   */
  protected function getMainPropertyName() {
    if ($this->field->isConfigProperty()) {
      throw new \Exception("Main property name not used when processing config properties.");
    }
    return $this->pluginDefinition['mainPropertyName'];
  }

  /**
   * Processes the properties for a single field value.
   *
   * @param array $value
   *   An array of field properties.
   *
   * @return array
   *   returns the array of column values for one field value.
   */
  protected function processValue($value) {
    return $value;
  }

}