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
    $entity_type_id = $this->fieldInfo->getSetting('target_type');
    $entity_definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $id_key = $entity_definition->getKey('id');

    // User entities return FALSE for getKey('label'), so use 'name' directly.
    $label_key = $entity_type_id !== 'user' ? $entity_definition->getKey('label') : 'name';

    // Determine target bundle restrictions.
    $target_bundles = $this->getTargetBundles();
    $target_bundle_key = $target_bundles ? $entity_definition->getKey('bundle') : NULL;

    $ids = [];

    foreach ((array) $values as $value) {
      $query = \Drupal::entityQuery($entity_type_id);
      $query->accessCheck(FALSE);

      if ($label_key) {
        $is_numeric_id = is_int($value) || (is_string($value) && ctype_digit($value));
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

      if ($target_bundles && $target_bundle_key) {
        $query->condition($target_bundle_key, $target_bundles, 'IN');
      }

      $entities = $query->execute();

      if (!$entities) {
        throw new \Exception(sprintf("No entity '%s' of type '%s' exists.", $value, $entity_type_id));
      }

      $ids[] = array_shift($entities);
    }

    return $ids;
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
