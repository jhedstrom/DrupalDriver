<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Entity\RevisionableInterface;

/**
 * Entity Reference Revisions field handler.
 *
 * Handles the 'entity_reference_revisions' field type (used by Paragraphs
 * among others). Resolves targets the same way 'EntityReferenceHandler' does
 * and additionally populates 'target_revision_id' with the current revision
 * id of each resolved target, producing storage entries shaped like
 * '['target_id' => X, 'target_revision_id' => Y]'.
 */
class EntityReferenceRevisionsHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $entity_type_id = $this->fieldInfo->getSetting('target_type');
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_definition = $entity_type_manager->getDefinition($entity_type_id);
    $id_key = $entity_definition->getKey('id');
    $label_key = $entity_type_id !== 'user' ? $entity_definition->getKey('label') : 'name';
    $main_property = $this->fieldInfo->getMainPropertyName();

    $target_bundles = $this->getTargetBundles();
    $target_bundle_key = $target_bundles ? $entity_definition->getKey('bundle') : NULL;

    $storage = $entity_type_manager->getStorage($entity_type_id);
    $resolved = [];

    foreach ((array) $values as $value) {
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
      $target = $storage->load($resolved_id);
      $revision_id = $target instanceof RevisionableInterface ? $target->getRevisionId() : NULL;

      if ($has_extras) {
        $value[$main_property] = $resolved_id;
        if ($revision_id !== NULL && !array_key_exists('target_revision_id', $value)) {
          $value['target_revision_id'] = $revision_id;
        }
        $resolved[] = $value;
      }
      else {
        $resolved[] = [
          'target_id' => $resolved_id,
          'target_revision_id' => $revision_id,
        ];
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
