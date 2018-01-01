<?php

namespace Drupal\Driver\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * A base class for Driver plugin annotation objects.
 */
class DriverBase extends Plugin {

  /**
   * @var string The plugin id.
   */
  public $id;

  /**
   * @var integer The priority of the plugin
   */
  public $weight = 0;

  /**
   * @var integer Whether this should be the last plugin processed.
   */
  public $final = FALSE;

}