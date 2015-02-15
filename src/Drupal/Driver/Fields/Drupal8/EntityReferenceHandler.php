<?php

/**
 * @file
 * Contains \Drupal\Driver\Fields\Drupal8\EntityReferenceHandler
 */

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Class EntityReferenceHandler
 * @package Drupal\Driver\Fields\Drupal8
 */
class EntityReferenceHandler extends AbstractHandler {

  /**
   * {@inheritDoc}
   */
  public function expand($values) {

    $return = array();
    $entity_type_id = $this->field_info->getSetting('target_type');
    $entity_definition = \Drupal::entityManager()->getDefinition($entity_type_id);
    $label = $entity_definition->getKey('label');
    foreach ($values as $value) {
      $entities = \Drupal::entityManager()
        ->getStorage($entity_type_id)
        ->loadByProperties(array($label => $value));
      if ($entities) {
        $return[] = array_shift($entities)->id();
      }
      else {
        throw new \Exception(sprintf("No entity '%s' of type '%s' exists.", $value, $entity_type_id));
      }
    }
    return $return;
  }
}
