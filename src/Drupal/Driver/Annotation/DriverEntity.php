<?php

namespace Drupal\Driver\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Driver entity plugin annotation object.
 *
 * @see \Drupal\Driver\Plugin\DriverEntityPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class DriverEntity extends Plugin
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
   * The machine names of the fields that might be used to reference this
   * entity.
   *
   * @var array
   */
    public $labelKeys;
}
