<?php
namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginBase;

/**
 * A driver field plugin for entity reference fields.
 *
 * @DriverField(
 *   id = "entity_reference",
 *   fieldTypes = {
 *     "entity_reference",
 *   },
 *   weight = -100,
 * )
 */
class EntityReference extends DriverFieldPluginBase {

  protected $entity_type_id;
  protected $label_key;
  protected $target_bundles;
  protected $target_bundle_key;

  /**
   * {@inheritdoc}
   */
  public function processValue($value) {
    $query = \Drupal::entityQuery($this->entity_type_id)->condition($this->label_key, $value);
    if ($this->target_bundles && $this->target_bundle_key) {
      $query->condition($this->target_bundle_key, $this->target_bundles, 'IN');
    }
    if ($entities = $query->execute()) {
      $target_id = array_shift($entities);
    }
    else {
      throw new \Exception(sprintf("No entity '%s' of type '%s' exists.", $value, $this->entity_type_id));
    }
    return ['target_id' => $target_id];
  }

  /**
   * {@inheritdoc}
   */
  public function processValues($values) {
    $this->entity_type_id = $this->field->getStorageDefinition()->getSetting('target_type');
    $entity_definition = \Drupal::entityManager()->getDefinition($this->entity_type_id);

    // Determine label field key.
    if ($this->entity_type_id !== 'user') {
      $this->label_key = $entity_definition->getKey('label');
    }
    else {
      // Entity Definition->getKey('label') returns false for users.
      $this->label_key = 'name';
    }

    // Determine target bundle restrictions.
    $this->target_bundle_key = NULL;
    if ($this->target_bundles = $this->getTargetBundles()) {
      $this->target_bundle_key = $entity_definition->getKey('bundle');
    }

    return parent::processValues($values);
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

}

