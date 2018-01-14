<?php

namespace Drupal\Driver\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Driver field plugin annotation object.
 *
 * @see \Drupal\Driver\Plugin\DriverFieldPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class DriverField extends Plugin
{

  /**
   * @var string The plugin id.
   */
    public $id;

  /**
   * The priority to give to this plugin.
   *
   * @var integer
   */
    public $weight = 0;

  /**
   * The Drupal major version being driven.
   *
   * @var integer
   */
    public $version;

  /**
   * Whether this should be the last plugin processed.
   *
   * @var integer
   */

    public $final = false;
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

  /**
   * The main property name for the field. Ignored for Drupal 8.
   *
   * @var string
   */
    public $mainPropertyName = 'value';
}
