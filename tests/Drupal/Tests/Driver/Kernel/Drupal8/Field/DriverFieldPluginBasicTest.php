<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;
use Drupal\Driver\Plugin\DriverFieldPluginManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Driver\Wrapper\Field\DriverFieldDrupal8;

/* Tests the field plugin base class.
 *
 * @group Field
 */
class DriverFieldPluginBasicTest extends DriverFieldKernelTestBase {

  /**
   * Tests the basic methods of the field plugin manager and base.
   */
  public function testPluginBase() {

    $namespaces = \Drupal::service('container.namespaces');
    $cache_backend = \Drupal::service('cache.discovery');
    $module_handler = \Drupal::service('module_handler');
    $fieldPluginManager = New DriverFieldPluginManager($namespaces, $cache_backend, $module_handler);
    $field = New DriverFieldDrupal8(
    [['value' => 'rawstring']],
    'somefieldname',
    'someentity'
    );

    print_r(($fieldPluginManager->processValues($field))->getProcessedValues());


  }

}
