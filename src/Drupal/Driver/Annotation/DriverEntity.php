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
class DriverEntity extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The priority to give to this plugin.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The Drupal major version being driven.
   *
   * @var int
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
   * The machine names of the fields this entity might be identified by.
   *
   * Typically an entity is identified in BDD by its label, but some Drupal
   * entities (e.g. user) don't declare a label, or could usefully be identified
   * to in other ways (e.g. for a user, either name or email address).
   *
   * @var array
   */
  public $labelKeys;

}
