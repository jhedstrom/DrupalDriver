<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\Driver\Plugin\DriverFieldPluginManager;
use Drupal\Driver\Plugin\DriverEntityPluginManager;
use Drupal\Driver\Wrapper\Field\DriverFieldDrupal8;
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;
use Drupal\entity_test\Entity\EntityTestBundle;

/** Tests the entity plugin base class.
 *
 * @group driver
 */
class DriverEntityWithBundlesTest extends DriverEntityKernelTestBase {

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
  protected $entityType = 'entity_test_with_bundle';

  /**
   * A field plugin manager object.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * A field plugin manager object.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginManagerInterface
   */
  protected $entityPluginManager;

  protected function setUp() {
    parent::setUp();
    $namespaces = \Drupal::service('container.namespaces');
    $cache_backend = \Drupal::service('cache.discovery');
    $module_handler = \Drupal::service('module_handler');
    $this->fieldPluginManager = New DriverFieldPluginManager($namespaces, $cache_backend, $module_handler, 8);
    $this->entityPluginManager = New DriverEntityPluginManager($namespaces, $cache_backend, $module_handler, 8);
    $this->installEntitySchema('entity_test_with_bundle');
    EntityTestBundle::create([
      'id' => 'test_bundle',
      'label' => 'Test label',
      'description' => 'Test description',
    ])->save();

  }

  /**
   * Test basic driver entity methods on an entity with bundles.
   */
  public function testLoadDeleteReload() {
    $value = $this->randomString();
    $fieldName = 'name';
    $processedName = 'now' . $value . 'processed';
    $field = New DriverFieldDrupal8(
      [['value' => $value]],
      $fieldName,
      $this->entityType
    );
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );
    $entity->setBundle('test_bundle');
    $entity->setFields([$fieldName => $field]);
    $entity->save();
    $entityId = $entity->id();


    // Test load method.
    $alternateEntity = New DriverEntityDrupal8(
      $this->entityType
    );
    $alternateEntity->load($entityId);
    $this->assertFalse($alternateEntity->isNew());
    $this->assertEquals('test_bundle', $alternateEntity->bundle());
    $this->assertEquals($processedName, $alternateEntity->get($fieldName)->value);

    // Test reload method
    $newValue = $this->randomString();
    $newProcessedName = 'now' . $newValue . 'processed';
    $entity->set($fieldName, [['value' => $newValue]]);
    $entity->save();
    $entities = $this->storage->loadByProperties([$fieldName => $newProcessedName]);
    $this->assertEquals(1, count($entities));
    // Alternate entity has stale value until reloaded.
    $this->assertNotEquals($newProcessedName, $alternateEntity->get($fieldName)->value);
    $alternateEntity->reload();
    $this->assertEquals($newProcessedName, $alternateEntity->get($fieldName)->value);

    // Test delete method.
    $alternateEntity->delete();
    $entities = $this->storage->loadByProperties([$fieldName => $newProcessedName]);
    $this->assertEquals(0, count($entities));
  }

  /**
   * Test setting a field on an entity with bundle.
   */
  public function testEntityWithBundle() {
    $value = $this->randomString();
    $fieldName = 'name';

    // Also tests passing the bundle in the create method and the constructor.
    $entity = DriverEntityDrupal8::create(
      [$fieldName => [['value' => $value]]],
      $this->entityType,
      'test_bundle'
    )->save();

    // Test bundle set properly
    $this->assertEquals($entity->bundle(), 'test_bundle');

    // The test driverfield plugin has been matched,  which mutates the text.
    $processedName = 'now' . $value . 'processed';
    $entities = $this->storage->loadByProperties(['name' => $processedName]);
    $this->assertEquals(1, count($entities));
  }

  /**
   * Test setting a nonexistent bundle.
   */
  public function testSetNonexistentBundle() {
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );
    $this->setExpectedException(\Exception::class, "'nonexistent_bundle' could not be identified as a bundle of the '" . $this->entityType);
    $entity->setBundle('nonexistent_bundle');
  }

  /**
   * Test setting a non existent bundle as a field.
   */
  public function testSetNonExistentBundleByField() {
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );

    $this->setExpectedException(\Exception::class, "No entity of type 'entity_test_bundle' has id or label matching");
    $entity->set('type', ['nonexistent bundle']);
  }

  /**
   * Test modifying an already set the bundle.
   */
  public function testModifyBundle() {
    EntityTestBundle::create([
      'id' => 'other_bundle',
      'label' => 'Other label',
      'description' => 'Other description',
    ])->save();
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );

    // Test exception when explicitly setting already set bundle bundle
    $entity->setBundle('test_bundle');
    $entity->getFinalPlugin();
    $this->setExpectedException(\Exception::class, "Cannot change entity bundle after final plugin discovery");
    $entity->setBundle('other_bundle');
  }

  /**
   * Test can identify bundle by label.
   */
  public function testEntityWithBundleByLabel() {
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );
    // test_bundle has the label "Test label"
    $entity->setBundle('test label');
    $this->assertEquals($entity->bundle(), 'test_bundle');
  }

  /**
   * Test extracting a bundle from among other fields, for various formats.
   */
  public function testCanExtractBundleFromFields() {
    $variants = [
      [['target_id' => 'Test label']],
      ['target_id' => 'Test label'],
      [['Test label']],
      ['Test label'],
    ];

    foreach ($variants as $variant) {
      // Test passing bundle as raw field.
      $this->assertCanExtractBundleFromFields($variant);

      // Test passing bundle as driverfield object.
      $field = New DriverFieldDrupal8(
        $variant,
        'type',
        $this->entityType,
        'test_bundle'
      );
      $this->assertCanExtractBundleFromFields($field);

    }

  }

  /**
   * Test extracting a bundle in a particular format from among other fields.
   *
   * @param array|object $variant
   *   A representation of a field identifying an entity's bundle.
   */
  public function assertCanExtractBundleFromFields($variant) {
    $value = $this->randomString();
    $fields = [
      'name' => [['value' => $value]],
      'type' => $variant,
      ];

    $entity = DriverEntityDrupal8::create(
      $fields,
      $this->entityType
    )->save();

    // Test bundle set properly
    $this->assertEquals($entity->bundle(), 'test_bundle');

    // The test driverfield plugin has been matched,  which mutates the text.
    $processedName = 'now' . $value . 'processed';
    $entities = $this->storage->loadByProperties(['name' => $processedName]);

    $bundleString = str_replace(PHP_EOL, '', print_r($variant, TRUE));
    $message = "Entity not created correctly when bundle input has value " . $bundleString;
    $this->assertEquals(1, count($entities), $message);
  }

}


