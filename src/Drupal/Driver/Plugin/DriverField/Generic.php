<?php
namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginBase;

/**
 * A driver field plugin that is a fallback for any field.
 *
 * @DriverField(
 *   id = "generic",
 *   weight = -100,
 * )
 */
class Generic extends DriverFieldPluginBase {
}