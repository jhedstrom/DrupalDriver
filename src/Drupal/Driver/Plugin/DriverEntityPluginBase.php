<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Driver\Wrapper\Entity\DriverEntityInterface;

/**
 * Provides a base class for the Driver's entity plugins.
 */
abstract class DriverEntityPluginBase extends PluginBase implements DriverEntityPluginInterface, DriverEntityInterface {

  /**
   * Entity type's machine name.
   *
   * @var string
   */
  protected $type;

  /**
   * Entity bundle's machine name.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $typeDefinition;

  /**
   * Entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The saved Drupal entity this object is wrapping for the Driver.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The driver field plugin manager.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * The directory to search for additional project-specific driver plugins.
   *
   * @var string
   */
  protected $projectPluginRoot;

  /**
   * {@inheritdoc}
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!is_string($configuration['type'])) {
      throw new \Exception("Entity type is required to initiate entity plugin.");
    }
    $this->type = $configuration['type'];
    $this->bundle = $configuration['bundle'];
    if (isset($configuration['fieldPluginManager'])) {
      $this->fieldPluginManager = $configuration['fieldPluginManager'];
    }
    if (isset($configuration['projectPluginRoot'])) {
      $this->projectPluginRoot = $configuration['projectPluginRoot'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __call($name, $arguments) {
    // Forward unknown calls to the entity.
    if (!$this->hasEntity()) {
      throw new \Exception("Method '$name' unknown on Driver entity plugin and entity not yet available.");
    }
    else {
      if (method_exists($this->getEntity(), $name)) {
        return call_user_func_array(array($this->getEntity(), $name), $arguments);
      }
      throw new \Exception("Method '$name' unknown on both Driver entity plugin and attached Drupal entity.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    // Forward unknown gets to the entity.
    if (!$this->hasEntity()) {
      throw new \Exception("Property '$name' unknown on Driver entity plugin and entity not yet available.");
    }
    else {
      if (property_exists($this->getEntity(), $name)) {
        return $this->getEntity()->$name;
      }
      throw new \Exception("Property '$name' unknown on both Driver entity plugin and attached Drupal entity.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if (!$this->hasEntity()) {
      $this->entity = $this->getNewEntity();
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelKeys() {
    if (isset($this->pluginDefinition['labelKeys'])) {
      return $this->pluginDefinition['labelKeys'];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return $this->hasEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->delete();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields) {
    foreach ($fields as $identifier => $field) {
      $this->set($identifier, $field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsBundles() {
    // In D8 bundle key is returned as empty but not null if entity type has
    // no bundle key.
    return !(empty($this->getBundleKey()));
  }

  /**
   * Get the driver field plugin manager.
   *
   * @return \Drupal\Driver\Plugin\DriverPluginManagerInterface
   *   The driver field plugin manager
   */
  protected function getFieldPluginManager() {
    return $this->fieldPluginManager;
  }

  /**
   * Whether a Drupal entity has already been instantiated and attached.
   *
   * @return bool
   *   Whether a Drupal entity is already attached to this plugin.
   */
  protected function hasEntity() {
    return !is_null($this->entity);
  }

}
