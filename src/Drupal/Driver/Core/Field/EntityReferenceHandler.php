<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Entity Reference field handler for Drupal 8.
 */
class EntityReferenceHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $return = [];
    $entity_type_id = $this->fieldInfo->getSetting('target_type');
    $entity_definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    $id_key = $entity_definition->getKey('id');

    // User entities return FALSE for getKey('label'), so use 'name' directly.
    $label_key = $entity_type_id !== 'user' ? $entity_definition->getKey('label') : 'name';

    // Determine target bundle restrictions.
    $target_bundle_key = NULL;
    if ($target_bundles = $this->getTargetBundles()) {
      $target_bundle_key = $entity_definition->getKey('bundle');
    }

    foreach ((array) $values as $value) {
      $query = \Drupal::entityQuery($entity_type_id);
      $is_numeric_id = is_int($value) || (is_string($value) && ctype_digit($value));
      if ($label_key) {
        $or = $query->orConditionGroup();
        if ($is_numeric_id) {
          $or->condition($id_key, (int) $value);
        }
        $or->condition($label_key, $value);
        $query->condition($or);
      }
      else {
        $query->condition($id_key, $value);
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
  protected function getTargetBundles(): mixed {
    $settings = $this->fieldConfig->getSettings();
    if (!empty($settings['handler_settings']['target_bundles'])) {
      return $settings['handler_settings']['target_bundles'];
    }
    return NULL;
  }

}
