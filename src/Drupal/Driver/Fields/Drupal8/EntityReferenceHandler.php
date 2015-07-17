<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal8\EntityReferenceHandler.
 */

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Entity Reference field handler for Drupal 8.
 */
class EntityReferenceHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    $entity_type_id = $this->fieldInfo->getSetting('target_type');
    $entity_definition = \Drupal::entityManager()->getDefinition($entity_type_id);
    $label_key = $entity_definition->getKey('label');

    // Determine target bundle restrictions.
    $settings = $this->fieldConfig->getSettings();
    if (!empty($settings['handler_settings']['target_bundles'])) {
      $target_bundle_key = $entity_definition->getKey('bundle');
      $target_bundles = $settings['handler_settings']['target_bundles'];
    }

    foreach ($values as $value) {
      $query = \Drupal::entityQuery($entity_type_id)->condition($label_key, $value);
      if (isset($target_bundles)) {
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

}
