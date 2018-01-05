<?php

namespace Drupal\Driver\Annotation;

use Drupal\Driver\Annotation\DriverBase;
/**
 * Defines a Driver field plugin annotation object.
 *
 * @see \Drupal\Driver\Plugin\DriverFieldPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class DriverField extends DriverBase {

  /**
   * The machine names of the fields the plugin targets.
   *
   * @var array
   */
  public $fieldNames;

  /**
   * The machines names of the field types the plugin targets.
   *
   * @var array
   */
  public $fieldTypes;

  /**
   * The machines names of the entity types the plugin targets.
   *
   * @var array
   */
  public $entityTypes;

  /**
   * The machine names of the entity bundles the plugin targets.
   *
   * @var array
   */
  public $entityBundles;

}