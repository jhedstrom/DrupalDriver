<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Entity Reference field handler for Drupal 8.
 */
class EntityReferenceHandler extends AbstractHandler {

  /**
   * @var string
   */
  protected $targetEntityTypeId;

  /**
   * @var string
   */
  protected $labelKey;

  /**
   * @var string|null
   */
  protected $targetBundleKey = NULL;

  /**
   * @var string[]|null
   */
  protected $targetBundles = NULL;

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $this->targetEntityTypeId = $this->fieldInfo->getSetting('target_type');

    // Determine label field key.
    if ($this->targetEntityTypeId !== 'user') {
      $this->labelKey = $this->getEntityTypeKey($this->targetEntityTypeId, 'label');
    }
    else {
      // Entity Definition->getKey('label') returns false for users.
      $this->labelKey = 'name';
    }

    if (!isset($this->labelKey) && $this->targetEntityTypeId === 'user') {
      $this->labelKey = 'name';
    }

    // Determine target bundle restrictions.
    if ($this->targetBundles = $this->getTargetBundles()) {
      $this->targetBundleKey = $this->getTargetEntityTypeKey($this->targetEntityTypeId, 'bundle');
    }

    // The values can either be a direct label reference or a complex array
    // containing multiple properties of the field. For example, the file field
    // contains a target_id, a description and a display property. If the
    // target_id exists as a property, we assume that the other properties are
    // also present. Retrieve all labels and load the entities.
    $main_property = $this->fieldInfo->getMainPropertyName();
    $values = (array) $values;
    $labels = array_map(function ($value) use ($main_property) {
      return is_array($value) && isset($value[$main_property]) ? $value[$main_property] : $value;
    }, $values);

    foreach ($labels as $delta => $label) {
      $entity_id = $this->getEntityReferenceIdFromLabel($label);
      // Replace the entity IDs in the original array so that other properties
      // are not lost.
      if (is_array($values[$delta]) && isset($values[$delta][$main_property])) {
        $values[$delta][$main_property] = $entity_id;
      }
      else {
        $values[$delta] = $entity_id;
      }
    }

    return $values;
  }

  /**
   * Retrieves bundles for which the field is configured to reference.
   *
   * @return string[]|null
   *   Array of bundle names, or NULL if not able to determine bundles.
   */
  protected function getTargetBundles(): ?array {
    $settings = $this->fieldConfig->getSettings();
    if (!empty($settings['handler_settings']['target_bundles'])) {
      return $settings['handler_settings']['target_bundles'];
    }
    return NULL;
  }

  /**
   * Returns the target entity ID given its label.
   *
   * @param string $label
   *   The target entity label.
   *
   * @return string|int
   *   The target entity ID.
   *
   * @throws \Exception
   *   When no target entity exists.
   */
  protected function getEntityReferenceIdFromLabel(string $label) {
    $query = \Drupal::entityQuery($this->targetEntityTypeId)
      ->accessCheck(FALSE)
      ->condition($this->labelKey, $label);
    if ($this->targetBundleKey && $this->targetBundles) {
      $query->condition($this->targetBundleKey, $this->targetBundles, 'IN');
    }
    if ($entities = $query->execute()) {
      return array_shift($entities);
    }
    throw new \Exception(sprintf("No entity '%s' of type '%s' exists.", $label, $this->targetEntityTypeId));
  }

}
