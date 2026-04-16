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
    $entityTypeId = $this->fieldInfo->getSetting('target_type');
    $entityDefinition = \Drupal::entityTypeManager()->getDefinition($entityTypeId);

    $idKey = $entityDefinition->getKey('id');

    // Determine label field key.
    if ($entityTypeId !== 'user') {
      $labelKey = $entityDefinition->getKey('label');
    }
    else {
      // Entity Definition->getKey('label') returns false for users.
      $labelKey = 'name';
    }

    if (!$labelKey && $entityTypeId == 'user') {
      $labelKey = 'name';
    }

    // Determine target bundle restrictions.
    $targetBundleKey = NULL;
    if ($targetBundles = $this->getTargetBundles()) {
      $targetBundleKey = $entityDefinition->getKey('bundle');
    }

    foreach ((array) $values as $value) {
      $query = \Drupal::entityQuery($entityTypeId);
      $isNumericId = is_int($value) || (is_string($value) && ctype_digit($value));
      if ($labelKey) {
        $or = $query->orConditionGroup();
        if ($isNumericId) {
          $or->condition($idKey, (int) $value);
        }
        $or->condition($labelKey, $value);
        $query->condition($or);
      }
      else {
        $query->condition($idKey, $value);
      }
      $query->accessCheck(FALSE);
      if ($targetBundles && $targetBundleKey) {
        $query->condition($targetBundleKey, $targetBundles, 'IN');
      }
      if ($entities = $query->execute()) {
        $return[] = array_shift($entities);
      }
      else {
        throw new \Exception(sprintf("No entity '%s' of type '%s' exists.", $value, $entityTypeId));
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
