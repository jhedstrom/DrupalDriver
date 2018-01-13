<?php
namespace Drupal\Driver\Plugin\DriverEntity;

use Drupal\Driver\Plugin\DriverEntityPluginDrupal8Base;

/**
 * A driver field plugin used to test selecting an arbitrary plugin.
 *
 * @DriverEntity(
 *   id = "test8",
 *   version = 8,
 *   weight = -1000,
 *   entityTypes = {
 *     "entity_test",
 *   },
 * )
 */
class TestDrupal8 extends DriverEntityPluginDrupal8Base {
}