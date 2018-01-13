<?php

namespace Drupal\Driver\Plugin;

/**
 * Provides the plugin manager for the Driver's entity plugins.
 */
class DriverEntityPluginManager extends DriverPluginManagerBase {

  /**
   * {@inheritdoc}
   */
  protected $driverPluginType = 'DriverEntity';

  /**
   * {@inheritdoc}
   */
  protected $filters = [
    'entityBundles',
    'entityTypes'
  ];

  /**
   * {@inheritdoc}
   */
  protected $specificityCriteria = [
    ['entityBundles', 'entityTypes'],
    ['entityBundles'],
    ['entityTypes'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFilterableTarget($entity) {
    return [
      'entityTypes' =>$entity->getEntityTypeId(),
      'entityBundles' => $entity->bundle()
    ];
  }

}