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
  public function processValues($values) {
    $processed = [];
    foreach ($values as $value) {
      $value = $this->assignColumnNames($value);
      $processed[] = $this->processValue($value);
    }
    return $processed;
  }

  /**
   * Converts a single string instruction into a field value.
   *
   * @return array
   *   returns the array of column values for one field value.
   */
  protected function processValue($value) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isFinal($field) {
    return $this->pluginDefinition['final'];
  }

  /**
   * Converts a single string instruction into a field value.
   *
   * @return array
   *   returns the array of column values for one field value.
   */
  protected function assignColumnNames($value) {
    if (!is_array($value)) {
      $value = ['value' => $value];
    }
    return $value;
  }

}