<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;
use Drupal\Driver\Plugin\DriverFieldPluginManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Driver\Wrapper\Field\DriverFieldDrupal8;

/* Tests the field plugin base class.
 *
 * @group driver
 */
class DriverFieldTest extends DriverFieldKernelTestBase {

  /**
   * Tests the basic methods of the field plugin manager and base.
   */
  public function testFieldPlugin() {
    $namespaces = \Drupal::service('container.namespaces');
    $cache_backend = \Drupal::service('cache.discovery');
    $module_handler = \Drupal::service('module_handler');
    $fieldPluginManager = New DriverFieldPluginManager($namespaces, $cache_backend, $module_handler);
    $value = $this->randomString();
    $fieldName = 'name';
    $entityType = 'entity_test';

    $field = New DriverFieldDrupal8(
      $fieldPluginManager,
    [['value' => $value]],
    $fieldName,
    $entityType
    );

    // Check the field object is instantiated correctly.
    $this->assertEquals($value, $field->getRawValues()[0]['value']);
    $this->assertEquals($entityType, $field->getEntityType());
    // Bundle defaults to entity type if not supplied.
    $this->assertEquals($entityType, $field->getBundle());
    $this->assertEquals($fieldName, $field->getName());

    // Check plugin discovery.
    // 2 plugins should be discovered: generic and test.
    $matchingDefinitions = $field->getFieldPluginManager()->getMatchedDefinitions($field);
    $this->assertEquals(2, count($matchingDefinitions), "Expected to discover exactly 1 matching driverfield plugin.");

    // Check field values are processed properly by the plugins.
    $processed = $field->getProcessedValues();
    $this->assertEquals(1, count($processed));
    $this->assertEquals(1, count($processed[0]));
    $this->assertEquals('now' . $value . 'processed', $processed[0]['value']);
  }
}
