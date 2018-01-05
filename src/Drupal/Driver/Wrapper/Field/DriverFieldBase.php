<?php

namespace Drupal\Driver\Wrapper\Field;

use Drupal\Driver\Plugin\DriverFieldPluginManager;

/**
 * A base class for a Driver field object that holds information about a Drupal
 * entity field.
 */
abstract class DriverFieldBase implements DriverFieldInterface {

  /**
   * Field name.
   *
   * @var string
   */
  protected $name;

  /**
   * Entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * General field definition (D7 field definition, D8: field_config).
   *
   * @var object|array
   */
  protected $definition;

  /**
   * Particular field definition (D7 field instance definition, D8:
   * field_storage_config).
   *
   * @var object|array
   */
  protected $storageDefinition;

  /**
   * Raw field values before processing by DriverField plugins.
   *
   * @var array
   */
  protected $rawValues;

  /**
   * Field values after processing by DriverField plugins.
   *
   * @var array
   */
  protected $processedValues;

  /**
   * A driver field plugin manager object.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(DriverFieldPluginManager $fieldPluginManager,
                              array $rawValues,
                              $fieldName,
                              $entityType,
                              $bundle = NULL) {
    if (empty($bundle)) {
      $bundle = $entityType;
    }
    $this->setRawValues($rawValues);
    $this->setName($fieldName);
    $this->setEntityType($entityType);
    $this->setBundle($bundle);
    $this->setFieldPluginManager($fieldPluginManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessedValues() {
    if (is_null($this->processedValues)) {
      $this->setProcessedValues($this->getRawValues());
      $fieldPluginManager = $this->getFieldPluginManager();
      $definitions = $fieldPluginManager->getMatchedDefinitions($this);
      foreach ($definitions as $definition) {
        $plugin = $fieldPluginManager->createInstance($definition['id'], ['field' => $this]);
        $processedValues = $plugin->processValues($this->processedValues);
        $this->setProcessedValues($processedValues);
        if ($plugin->isFinal($this)) {
          break;
        };
      }
    }
    return $this->processedValues;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {

  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType() {
    return 'string';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawValues() {
    return $this->rawValues;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageDefinition() {
    return $this->storageDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldPluginManager() {
    return $this->fieldPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setRawValues(array $values) {
    $this->rawValues = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessedValues(array $values) {
    $this->processedValues = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefinition($definition) {
    $this->definition = $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function setStorageDefinition($definition) {
    $this->storageDefinition = $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldPluginManager($fieldPluginManager) {
    $this->fieldPluginManager = $fieldPluginManager;
  }
}