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

  /* All of these should have proper doc comments */
  public $id;

  /**
   * @var array
   */
  public $fieldNames;

  /**
   * @var array
   */
  public $fieldTypes;

  /**
   * @var array
   */
  public $entityTypes;

  /**
   * @var array
   */
  public $entityBundles;

  /**
   * @var integer
   */
  public $weight = 0;

}