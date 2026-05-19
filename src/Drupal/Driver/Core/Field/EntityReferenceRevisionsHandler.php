<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Entity\RevisionableInterface;

/**
 * Field handler for 'entity_reference_revisions' fields (Paragraphs et al).
 */
class EntityReferenceRevisionsHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $entity_type_id = $this->fieldInfo->getSetting('target_type');
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_definition = $entity_type_manager->getDefinition($entity_type_id);
    $id_key = $entity_definition->getKey('id');
    $label_key = $entity_type_id !== 'user' ? $entity_definition->getKey('label') : 'name';

    $target_bundles = $this->getTargetBundles();
    $target_bundle_key = $target_bundles ? $entity_definition->getKey('bundle') : NULL;

    $storage = $entity_type_manager->getStorage($entity_type_id);
    $resolved = [];

    foreach ($records as $record) {
      $lookup = $record[$this->mainProperty];

      if (is_int($lookup)) {
        $resolved_id = $lookup;
      }
      else {
        $query = \Drupal::entityQuery($entity_type_id);
        $query->accessCheck(FALSE);

        if ($label_key) {
          $is_numeric_id = is_string($lookup) && ctype_digit($lookup);
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
      }

      $target = $storage->load($resolved_id);
      $record[$this->mainProperty] = $resolved_id;

      if (!array_key_exists('target_revision_id', $record)) {
        $record['target_revision_id'] = $target instanceof RevisionableInterface ? $target->getRevisionId() : NULL;
      }

      $resolved[] = $record;
    }

    return $resolved;
  }

  /**
   * Returns bundle restrictions configured on the field, or NULL.
   *
   * @return array<int|string, string>|null
   *   Bundle names the field may target, or NULL when unrestricted.
   */
  protected function getTargetBundles(): ?array {
    $settings = $this->fieldConfig->getSettings();

    if (!empty($settings['handler_settings']['target_bundles'])) {
      return $settings['handler_settings']['target_bundles'];
    }

    return NULL;
  }

}
