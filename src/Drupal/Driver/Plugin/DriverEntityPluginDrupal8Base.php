<?php

namespace Drupal\Driver\Plugin;

use Drupal\Driver\Wrapper\Field\DriverFieldInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Driver\Wrapper\Field\DriverFieldDrupal8;
use Drupal\Driver\Wrapper\Entity\DriverEntityInterface;

/**
 * Provides a base class for the Driver's entity plugins.
 */
class DriverEntityPluginDrupal8Base extends DriverEntityPluginBase implements DriverEntityPluginInterface, DriverEntityInterface {

  /**
   * The id of the attached entity.
   *
   * @var int|string
   *
   * @deprecated Use id() instead.
   */
  public $id;

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
   * The drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->storage = $this->entityTypeManager->getStorage($this->type);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->getEntity()->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleKey() {
    return $this->entityTypeManager
      ->getDefinition($this->type)
      ->getKey('bundle');
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleKeyLabels() {
    $bundleKeyLabel = NULL;
    $bundleKey = $this->getBundleKey();
    if (!empty($bundleKey)) {
      $definitions = \Drupal::service('entity_field.manager')
        ->getBaseFieldDefinitions($this->type);
      $bundleKeyLabel = $definitions[$bundleKey]->getLabel();
    }
    return [(string) $bundleKeyLabel];
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    $bundleInfo = \Drupal::service('entity_type.bundle.info')->getBundleInfo($this->type);
    // Parse into array structure used by DriverNameMatcher.
    $bundles = [];
    foreach ($bundleInfo as $machineName => $bundleSettings) {
      $bundles[$bundleSettings['label']] = $machineName;
    }
    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    $entity = parent::getEntity();
    if (!$entity instanceof EntityInterface) {
      throw new \Exception("Failed to obtain valid entity");
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelKeys() {
    $labelKeys = parent::getLabelKeys();
    if (empty($labelKeys)) {
      $labelKeys = [
        $this->entityTypeManager
          ->getDefinition($this->type)
          ->getKey('label'),
      ];
    }
    return $labelKeys;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->getEntity()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    if ($this->hasEntity() && !$this->entity->isNew()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getEntity()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function load($entityId) {
    if ($this->hasEntity()) {
      throw new \Exception("A Drupal entity is already attached to this plugin");
    }
    $this->entity = $this->getStorage()->load($entityId);
    $this->id = is_null($this->entity) ? NULL : $this->id();
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    if (!$this->hasEntity()) {
      throw new \Exception("There is no attached entity so it cannot be reloaded");
    }
    $entityId = $this->getEntity()->id();
    $this->getStorage()->resetCache([$entityId]);
    $this->entity = $this->getStorage()->load($entityId);
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->getEntity()->save();
    $this->id = $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function set($identifier, $field) {
    if (!($field instanceof DriverFieldInterface)) {
      $field = $this->getNewDriverField($identifier, $field);
    }
    $this->getEntity()->set($field->getName(), $field->getProcessedValues());
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', array $options = []) {
    return $this->getEntity()->url($rel, $options);
  }

  /**
   * Get a new driver field with values.
   *
   * @param string $fieldName
   *   A string identifying an entity field.
   * @param string|array $values
   *   An input that can be transformed into Driver field values.
   */
  protected function getNewDriverField($fieldName, $values) {
    $field = new DriverFieldDrupal8(
        $values,
        $fieldName,
        $this->type,
        $this->bundle,
        $this->projectPluginRoot,
        $this->fieldPluginManager
    );
    return $field;
  }

  /**
   * Get the entity type storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity type storage.
   */
  protected function getStorage() {
    return $this->storage;
  }

  /**
   * Get a new entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A Drupal entity object.
   */
  protected function getNewEntity() {
    $values = [];
    // Set the bundle as a field if not simply using the default for
    // a bundle-less entity type.
    if ($this->type !== $this->bundle) {
      $bundleKey = $this->getBundleKey();
      $values[$bundleKey] = $this->bundle;
    }
    $entity = $this->getStorage()->create($values);
    return $entity;
  }

}
