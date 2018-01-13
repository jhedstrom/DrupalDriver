<?php

namespace Drupal\Driver\Wrapper\Field;

use Drupal\Driver\Plugin\DriverPluginManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Component\Utility\DriverNameMatcher;

/**
 * A Driver field object that holds information about Drupal 8 field.
 */
class DriverFieldDrupal8 extends DriverFieldBase implements DriverFieldInterface {

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
   * Whether this driver field is wrapping the property of a config entity, not
   * the field of a content entity.
   *
   * @var boolean
   */
  protected $isConfigProperty = FALSE;

  /**
   * The config schema of this config entity property
   *
   * @var array
   */
  protected $configSchema;

  /**
   * The Drupal version being driven.
   *
   * @var integer
   */
  protected $version = 8;

  public function __construct($rawValues,
                              $fieldName,
                              $entityType,
                              $bundle = NULL,
                              $projectPluginRoot = NULL,
                              $fieldPluginManager = NULL) {
    $entityTypeDefinition = \Drupal::EntityTypeManager()
      ->getDefinition($entityType);
    if ($entityTypeDefinition->entityClassImplements(ConfigEntityInterface::class)) {
      $this->isConfigProperty = TRUE;
      $configPrefix = $entityTypeDefinition->getConfigPrefix();
      $configProperties = \Drupal::service('config.typed')->getDefinition("$configPrefix.*")['mapping'];
      $this->configSchema = $configProperties;
    }

    // Set Drupal environment variables used by default plugin manager.
    $this->namespaces = \Drupal::service('container.namespaces');
    $this->cache_backend = $cache_backend = \Drupal::service('cache.discovery');
    $this->module_handler = $module_handler = \Drupal::service('module_handler');

    parent::__construct($rawValues, $fieldName, $entityType, $bundle, $projectPluginRoot, $fieldPluginManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    if (is_null($this->definition) && !$this->isConfigProperty) {
      $entityFieldManager = \Drupal::service('entity_field.manager');
      $definitions = $entityFieldManager->getFieldDefinitions($this->getEntityType(), $this->getBundle());
      $this->definition = $definitions[$this->getName()];
    }
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageDefinition() {
    return $this->getDefinition()->getFieldStorageDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    if ($this->isConfigProperty) {
      return $this->configSchema[$this->getName()]['type'];
    }
    else {
      return $this->getDefinition()->getType();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigProperty() {
    return $this->isConfigProperty;
  }

  /**
   * Get the machine name of the field from a human-readable identifier.
   *
   * @return string
   *   The machine name of a field.
   */
  protected function identify($identifier) {
    // Get all the candidate fields. Assemble them into an array of field
    // machine names and labels ready for DriverNameMatcher. Read-only fields
    // are not removed because DriverFields can be used for comparing as well
    // as writing values.
    $candidates = [];
    if ($this->isConfigProperty()) {
      foreach ($this->configSchema as $id => $subkeys) {
        $label = isset($subkeys['label']) ? $subkeys['label'] : $id;
        $candidates[$label] = $id;
      }
    }
    else {
      $entityManager = \Drupal::service('entity_field.manager');
      $fields = $entityManager->getFieldDefinitions($this->entityType, $this->bundle);
      foreach ($fields as $machineName => $definition) {
        $label = (string) $definition->getLabel();
        $label = empty($label) ? $machineName : $label;
        $candidates[$label] = $machineName;
      }
    }

    $matcher = New DriverNameMatcher($candidates, "field_");
    $result = $matcher->identify($identifier);
    if (is_null($result)) {
      throw new \Exception("Field or property cannot be identified. '$identifier' does not match anything on '" . $this->getEntityType(). "'.");
    }
    return $result;
  }

}