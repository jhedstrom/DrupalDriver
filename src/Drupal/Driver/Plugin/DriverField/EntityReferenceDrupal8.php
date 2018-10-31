<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal8Base;
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;
use Drupal\Driver\Plugin\DriverEntityPluginManager;

/**
 * A driver field plugin for entity reference fields.
 *
 * @DriverField(
 *   id = "entity_reference",
 *   version = 8,
 *   fieldTypes = {
 *     "entity_reference",
 *   },
 *   weight = -100,
 * )
 */
class EntityReferenceDrupal8 extends DriverFieldPluginDrupal8Base {

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Machine names of the fields or properties to use as labels for targets.
   *
   * @var array
   */
  protected $labelKeys;

  /**
   * The machine name of the field or property to use as id for targets.
   *
   * @var string
   */
  protected $idKey;

  /**
   * The bundles that targets must belong to.
   *
   * @var string
   */
  protected $targetBundles;

  /**
   * The machine name of the field that holds the bundle reference for targets.
   *
   * @var string
   */
  protected $targetBundleKey;

  /**
   * {@inheritdoc}
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Determine id & label keys.
    $this->entityTypeId = $this->field->getStorageDefinition()->getSetting('target_type');
    $entity_definition = \Drupal::entityManager()->getDefinition($this->entityTypeId);
    $this->idKey = $entity_definition->getKey('id');
    $this->labelKeys = $this->getLabelKeys();

    // Determine target bundle restrictions.
    if ($this->targetBundles = $this->getTargetBundles()) {
      $this->targetBundleKey = $entity_definition->getKey('bundle');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processValue($value) {
    if (is_array($value['target_id'])) {
      throw new \Exception("Array value not expected: " . print_r($value['target_id'], TRUE));
    }

    // Build a set of strategies for matching target entities with the supplied
    // identifier text.
    // Id key is useful for matching config entities as they have string ids.
    // Id exact match takes precedence over label matches; label matches take
    // precedence over id key without underscores matches.
    $matchTypes = [];
    $matchTypes[] = ['key' => $this->idKey, 'value' => $value['target_id']];
    foreach ($this->labelKeys as $labelKey) {
      $matchTypes[] = ['key' => $labelKey, 'value' => $value['target_id']];
    }
    $matchTypes[] = [
      'key' => $this->idKey,
      'value' => str_replace(' ', '_', $value['target_id']),
    ];

    // Try various matching strategies until we find a match.
    foreach ($matchTypes as $matchType) {
      // Ignore this strategy if the needed key has not been determined.
      // D8 key look ups return empty strings if there is no key of that kind.
      if (empty($matchType['key'])) {
        continue;
      }
      $targetId = $this->queryByKey($matchType['key'], $matchType['value']);
      if (!is_null($targetId)) {
        break;
      }
    }

    if (is_null($targetId)) {
      throw new \Exception(sprintf("No entity of type '%s' has id or label matching '%s'.", $this->entityTypeId, $value['target_id']));
    }
    return ['target_id' => $targetId];
  }

  /**
   * Retrieves bundles for which the field is configured to reference.
   *
   * @return mixed
   *   Array of bundle names, or NULL if not able to determine bundles.
   */
  protected function getTargetBundles() {
    $settings = $this->field->getDefinition()->getSettings();
    if (!empty($settings['handler_settings']['target_bundles'])) {
      return $settings['handler_settings']['target_bundles'];
    }
  }

  /**
   * Retrieves fields to try as the label on the entity being referenced.
   *
   * @return array
   *   Array of field machine names.
   */
  protected function getLabelKeys() {
    $plugin = $this->getEntityPlugin();
    return $plugin->getLabelKeys();
  }

  /**
   * Get an entity plugin for the entity reference target entity type.
   *
   * @return \Drupal\Driver\Plugin\DriverEntityPluginInterface
   *   An instantiated driver entity plugin object.
   */
  protected function getEntityPlugin() {
    $projectPluginRoot = $this->field->getProjectPluginRoot();

    // Build the basic config for the plugin.
    $targetEntity = new DriverEntityDrupal8($this->entityTypeId);
    $config = [
      'type' => $this->entityTypeId,
      'projectPluginRoot' => $projectPluginRoot,
    ];

    // Get a bundle specific plugin only if the entity reference field is
    // targeting a single bundle.
    if (is_array($this->targetBundles) && count($this->targetBundles) === 1) {
      $config['bundle'] = $this->targetBundles[0];
      $targetEntity->setBundle($this->targetBundles[0]);
    }
    else {
      $config['bundle'] = $this->entityTypeId;
    }

    // Discover & instantiate plugin.
    $namespaces = \Drupal::service('container.namespaces');
    $cache_backend = $cache_backend = \Drupal::service('cache.discovery');
    $module_handler = $module_handler = \Drupal::service('module_handler');
    $manager = new DriverEntityPluginManager($namespaces, $cache_backend, $module_handler, $this->pluginDefinition['version'], $this->field->getProjectPluginRoot());

    // Get only the highest priority matched plugin.
    $matchedDefinitions = $manager->getMatchedDefinitions($targetEntity);
    if (count($matchedDefinitions) === 0) {
      throw new \Exception("No matching DriverEntity plugins found.");
    }
    $topDefinition = $matchedDefinitions[0];
    $plugin = $manager->createInstance($topDefinition['id'], $config);
    return $plugin;
  }

  /**
   * Find an entity by looking at id and labels keys.
   *
   * @param string $key
   *   The machine name of the field to query.
   * @param string $value
   *   The value to seek in the field.
   *
   * @return int|string
   *   The id of an entity that has $value in the $key field.
   */
  protected function queryByKey($key, $value) {
    $query = \Drupal::entityQuery($this->entityTypeId);
    // @todo make this always case-insensitive.
    $query->condition($key, $value);
    if ($this->targetBundles && $this->targetBundleKey) {
      $query->condition($this->targetBundleKey, $this->targetBundles, 'IN');
    }
    $entities = $query->execute();
    if ($entities = $query->execute()) {
      $target_id = array_shift($entities);
      return $target_id;
    }
  }

}
