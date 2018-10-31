<?php

namespace Drupal\Driver\Plugin\DriverEntity;

use Drupal\Driver\Plugin\DriverEntityPluginDrupal8Base;

/**
 * A driver field plugin that is a fallback for any field.
 *
 * @DriverEntity(
 *   id = "generic8",
 *   version = 8,
 *   weight = -100,
 * )
 */
class GenericDrupal8 extends DriverEntityPluginDrupal8Base {
}
