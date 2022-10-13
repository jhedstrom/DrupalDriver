<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Entity Reference field handler for Drupal 8.
 */
class EntityReferenceHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $entity_type_id = $this->fieldInfo->getSetting('target_type');
    $entity_definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    // Determine label field key.
    if ($entity_type_id !== 'user') {
      $label_key = $entity_definition->getKey('label');
    }
    else {
      // Entity Definition->getKey('label') returns false for users.
      $label_key = 'name';
    }

    if (!$label_key && $entity_type_id == 'user') {
      $label_key = 'name';
    }

    // Determine target bundle restrictions.
    $target_bundle_key = NULL;
    if ($target_bundles = $this->getTargetBundles()) {
      $target_bundle_key = $entity_definition->getKey('bundle');
    }

    // The values can either be a direct label reference or a complex array
    // containing multiple properties of the field. For example, the file field
    // contains a target_id, a description and a display property. If the
    // target_id exists as a property, we assume that the other properties are
    // also present. Retrieve all labels and load the entities.
    $main_property = $this->fieldInfo->getMainPropertyName();
    $labels = array_map(function ($value) use ($main_property) {
      return is_array($value) && isset($value[$main_property]) ? $value[$main_property] : $value;
    }, $values);

    foreach ((array) $labels as $index => $label) {
      $query = \Drupal::entityQuery($entity_type_id)->condition($label_key, $label);
      $query->accessCheck(FALSE);
      if ($target_bundles && $target_bundle_key) {
        $query->condition($target_bundle_key, $target_bundles, 'IN');
      }
      if ($entities = $query->execute()) {
        $entity_id = array_shift($entities);
        // Replace the entity IDs in the original array so that other properties
        // are not lost.
        if (is_array($values[$index]) && isset($values[$index][$main_property])) {
          $values[$index][$main_property] = $entity_id;
        }
        else {
          $values[$index] = $entity_id;
        }
      }
      else {
        throw new \Exception(sprintf("No entity '%s' of type '%s' exists.", $label, $entity_type_id));
      }
    }
    return $values;
  }

  /**
   * Retrieves bundles for which the field is configured to reference.
   *
   * @return mixed
   *   Array of bundle names, or NULL if not able to determine bundles.
   */
  protected function getTargetBundles() {
    $settings = $this->fieldConfig->getSettings();
    if (!empty($settings['handler_settings']['target_bundles'])) {
      return $settings['handler_settings']['target_bundles'];
    }
  }

}
