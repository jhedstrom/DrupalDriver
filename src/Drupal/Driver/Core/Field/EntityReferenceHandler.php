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
    $main_property = $this->fieldInfo->getMainPropertyName();

    // Determine target bundle restrictions.
    $target_bundles = $this->getTargetBundles();
    $target_bundle_key = $target_bundles ? $entity_definition->getKey('bundle') : NULL;

    $resolved = [];

    foreach ((array) $values as $value) {
      // A delta may be a scalar (label or id) OR an associative array carrying
      // the main property plus additional item columns (e.g. 'display' on file
      // references or 'target_revision_id' on entity_reference_revisions).
      // When the main property is present, use its value as the lookup label
      // and preserve the rest of the array so those extras round-trip through
      // to storage.
      $has_extras = is_string($main_property) && is_array($value) && array_key_exists($main_property, $value);
      $lookup = $has_extras ? $value[$main_property] : $value;

      $query = \Drupal::entityQuery($entity_type_id);
      $query->accessCheck(FALSE);

      if ($label_key) {
        $is_numeric_id = is_int($lookup) || (is_string($lookup) && ctype_digit($lookup));
        $or = $query->orConditionGroup();

        if ($is_numeric_id) {
          $or->condition($id_key, (int) $lookup);
        }

        $or->condition($label_key, $lookup);
        $query->condition($or);
      }
      else {
        $query->condition($id_key, $lookup);
      }

      if ($target_bundles && $target_bundle_key) {
        $query->condition($target_bundle_key, $target_bundles, 'IN');
      }

      $entities = $query->execute();

      if (!$entities) {
        throw new \Exception(sprintf("No entity '%s' of type '%s' exists.", $lookup, $entity_type_id));
      }

      $resolved_id = array_shift($entities);

      if ($has_extras) {
        $value[$main_property] = $resolved_id;
        $resolved[] = $value;
      }
      else {
        $resolved[] = $resolved_id;
      }
    }

    return $resolved;
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
