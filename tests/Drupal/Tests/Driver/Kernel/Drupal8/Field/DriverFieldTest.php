<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;
use Drupal\Driver\Plugin\DriverFieldPluginManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Driver\Wrapper\Field\DriverFieldDrupal8;

/** Tests the field plugin base class.
 *
 * @group driver
 */
class DriverFieldTest extends DriverFieldKernelTestBase {

  /**
   * Field plugin manager.
   *
   * * @var \Drupal\Driver\Plugin\DriverPluginManagerInterface;
   */
  protected $fieldPluginManager;

  /**
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();
    $namespaces = \Drupal::service('container.namespaces');
    $cache_backend = \Drupal::service('cache.discovery');
    $module_handler = \Drupal::service('module_handler');
    $this->fieldPluginManager = New DriverFieldPluginManager($namespaces, $cache_backend, $module_handler, 8);
  }

  /**
   * Test identifying field by machine name.
   */
  public function testFieldIdentifiedByMachineName() {
    $fieldName = $this->createFieldForDriverTest('string',
      1,
      [],
      [],
      '',
      $this->entityType,
      $this->entityType,
      "test");

    $this->assertFieldIdentified($fieldName, $fieldName);
  }

  /**
   * Test identifying field by machine name, case insensitively.
   */
  public function testFieldIdentifiedByMachineNameUC() {
    $fieldName = $this->createFieldForDriverTest('string',
      1,
      [],
      [],
      '',
      $this->entityType,
      $this->entityType,
      "test");

    $this->assertFieldIdentified(strtoupper($fieldName), $fieldName);
  }

  /**
   * Test identifying field by label.
   */
  public function testFieldIdentifiedByLabel() {
    $fieldName = $this->createFieldForDriverTest('string',
      1,
      [],
      [],
      '',
      $this->entityType,
      $this->entityType,
      "test");
    $fieldLabel = $this->fieldTestData->field_definition['label'];

    $this->assertFieldIdentified($fieldLabel, $fieldName);
  }

  /**
   * Test identifying field by label, case insensitively.
   */
  public function testFieldIdentifiedByLabelUC() {
    $fieldName = $this->createFieldForDriverTest('string',
      1,
      [],
      [],
      '',
      $this->entityType,
      $this->entityType,
      "test");
    $fieldLabel = $this->fieldTestData->field_definition['label'];

    $this->assertFieldIdentified(strtoupper($fieldLabel), $fieldName);
  }

  /**
   * Test identifying field by machine name, case insensitively.
   */
  public function testFieldIdentifiedByMachineNameWithoutUnderscores() {
    $fieldName = $this->createFieldForDriverTest('string',
      1,
      [],
      [],
      '',
      $this->entityType,
      $this->entityType,
      "test");

    // The field name is test_field_name
    $this->assertFieldIdentified(strtoupper("test field name"), $fieldName);
  }

  /**
   * Test identifying field by machine name, case insensitively.
   */
  public function testFieldIdentifiedByMachineNameWithoutPrefix() {
    $fieldName = $this->createFieldForDriverTest('string',
      1,
      [],
      [],
      '',
      $this->entityType,
      $this->entityType,
      "field_test");

    // The field name is field_test_field_name
    $this->assertFieldIdentified(strtoupper("test_field_name"), $fieldName);
  }

  /**
   * Test identifying field by machine name, case insensitively.
   */
  public function testFieldIdentifiedByMachineNameWithoutPrefixUnderscores() {
    $fieldName = $this->createFieldForDriverTest('string',
      1,
      [],
      [],
      '',
      $this->entityType,
      $this->entityType,
      "field_test");

    // The field name is field_test_field_name
    $this->assertFieldIdentified(strtoupper("test field name"), $fieldName);
  }

  /**
   * Tests the basic methods of the field plugin manager and base.
   *
   * @param string $identifier
   *   The string used to identify the field to be wrapped.
   * @param string $fieldName
   *   The machine name of the field being wrapped.
   *
   */
  protected function assertFieldIdentified($identifier, $fieldName) {
    $value = $this->randomString();
    $field = New DriverFieldDrupal8(
      [['value' => $value]],
      $identifier,
      $this->entityType
    );

    // Check the field object is instantiated correctly.
    $this->assertEquals($value, $field->getRawValues()[0]['value']);
    $this->assertEquals($this->entityType, $field->getEntityType());
    $this->assertEquals($fieldName, $field->getName());
    // Bundle defaults to entity type if not supplied.
    $this->assertEquals($this->entityType, $field->getBundle());
    $this->assertEquals($fieldName, $field->getName());

    // Check field values are processed properly by the plugins.
    $processed = $field->getProcessedValues();
    $this->assertEquals(1, count($processed));
    $this->assertEquals(1, count($processed[0]));
    $this->assertEquals('now' . $value . 'processed', $processed[0]['value']);
  }

}
