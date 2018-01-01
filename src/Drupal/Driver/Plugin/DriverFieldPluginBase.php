<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Driver\Plugin\DriverFieldPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Base class for Driver field plugins.
 */
class DriverFieldPluginBase extends PluginBase implements DriverFieldPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $processed[] = $this->processValue($value);
    }
    $this->validateValues($values);
    return $processed;
  }

  /**
   * {@inheritdoc}
   */
  public function processValue($value) {
    return $value;

  }

  /**
   * {@inheritdoc}
   */
  public function validateValues($values) {
  }


  /**
   * {@inheritdoc}
   */
  public function isFinal($field) {
    return $this->pluginDefinition['final'];
  }

}