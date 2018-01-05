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
   * The priority to give to this plugin.
   *
   * @var integer
   */
  public $weight = 0;

  /**
   * Whether this should be the last plugin processed.
   *
   * @var integer
   */
  public $final = FALSE;

}