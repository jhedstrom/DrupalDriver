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
    $return = [];
    $entity_type_id = $this->fieldInfo->getSetting('target_type');
    $entity_definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    $id_key = $entity_definition->getKey('id');

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

    // Determine the id key type (can be an integer or string).
    $id_definition = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($entity_type_id)[$id_key];
    $id_type = $id_definition->getType();

    foreach ((array) $values as $value) {
      $query = \Drupal::entityQuery($entity_type_id);
      // Provide for the use of numeric entity ids.
      if ($id_type === 'integer' && is_numeric($value)) {
        $query->condition($id_key, $value);
      } else {
        $query->condition($label_key, $value);
      }
      $query->accessCheck(FALSE);
      if ($target_bundles && $target_bundle_key) {
        $query->condition($target_bundle_key, $target_bundles, 'IN');
      }
      if ($entities = $query->execute()) {
        $return[] = array_shift($entities);
      }
      else {
        throw new \Exception(sprintf("No entity '%s' of type '%s' exists.", $value, $entity_type_id));
      }
    }
    return $return;
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
