<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'entity_reference' fields.
 */
class EntityReferenceHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $entity_type_id = $this->fieldInfo->getSetting('target_type');
    $entity_definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $id_key = $entity_definition->getKey('id');

    // User entities return FALSE for getKey('label'), so use 'name' directly.
    $label_key = $entity_type_id !== 'user' ? $entity_definition->getKey('label') : 'name';

    $target_bundles = $this->getTargetBundles();
    $target_bundle_key = $target_bundles ? $entity_definition->getKey('bundle') : NULL;

    $resolved = [];

    foreach ($records as $record) {
      if (!array_key_exists($this->mainProperty, $record)) {
        throw new \InvalidArgumentException(sprintf('Entity reference record is missing the main property "%s".', $this->mainProperty));
      }

      $lookup = $record[$this->mainProperty];

      // Already-resolved integer ids (caller-supplied or alias-resolved)
      // bypass the entity-storage round-trip; only string labels still
      // need a lookup.
      if (is_int($lookup)) {
        $resolved[] = $record;
        continue;
      }

      $query = \Drupal::entityQuery($entity_type_id);
      $query->accessCheck(FALSE);

      if ($label_key) {
        // A numeric-string lookup is ambiguous - the caller may be passing
        // an entity id that Drupal serialised as a string, or a label that
        // happens to be digits. Match either side with an OR-group so the
        // entity layer's first hit wins.
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

      $record[$this->mainProperty] = array_shift($entities);
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
