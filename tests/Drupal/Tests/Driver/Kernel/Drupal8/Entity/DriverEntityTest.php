<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Driver\Wrapper\Field\DriverFieldDrupal8;
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;

/** Tests the entity plugin base class.
 *
 * @group driver
 */
class DriverEntityTest extends DriverEntityKernelTestBase {

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
  protected $entityType = 'entity_test';

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

  }

  /**
   * Test various ways of setting field values on entities.
   */
  public function testField() {
    // Value & property explicit.
    $fieldValues =  [['value' => 'NAME']];
    $this->assertEntitySetFieldsWithObject($fieldValues);
    $this->assertEntitySetFieldsWithArray($fieldValues);
    $this->assertEntitySet($fieldValues);

    // Value explicit, property assumed.
    $fieldValues =  [['NAME']];
    $this->assertEntitySetFieldsWithObject($fieldValues);
    $this->assertEntitySetFieldsWithArray($fieldValues);
    $this->assertEntitySet($fieldValues);

    // Single value assumed, property explicit.
    $fieldValues =  ['value' => 'NAME'];
    $this->assertEntitySetFieldsWithObject($fieldValues);
    $this->assertEntitySetFieldsWithArray($fieldValues);
    $this->assertEntitySet($fieldValues);

    // Single value assumed, property assumed.
    $fieldValues =  'NAME';
    $this->assertEntitySetFieldsWithObject($fieldValues);
    $this->assertEntitySetFieldsWithArray($fieldValues);
    $this->assertEntitySet($fieldValues);
  }

  /**
   * Assert that an entity is created using the setFields method and an field
   * values array.
   */
  protected function assertEntitySetFieldsWithArray($fieldValues) {
    $value = $this->randomString();
    $fieldValues = $this->recursiveReplace('NAME', $value, $fieldValues);
    $fieldName = 'name';
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );
    $entity->setFields([$fieldName => $fieldValues]);
    $this->assertEntityWithName($value, $fieldName, $entity);
  }

  /**
   * Assert that an entity is created using the setFields method and a driver
   * field object.
   */
  protected function assertEntitySetFieldsWithObject($fieldValues) {
    $value = $this->randomString();
    $fieldValues = $this->recursiveReplace('NAME', $value, $fieldValues);
    $fieldName = 'name';
    $field = New DriverFieldDrupal8(
      $fieldValues,
      $fieldName,
      $this->entityType
    );
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );
    $entity->setFields([$fieldName => $field]);
    $this->assertEntityWithName($value, $fieldName, $entity);
  }

  /**
   * Assert that an entity is created using the set method.
   */
  protected function assertEntitySet($fieldValues) {
    $value = $this->randomString();
    $fieldValues = $this->recursiveReplace('NAME', $value, $fieldValues);
    $fieldName = 'name';
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );
    $entity->set($fieldName, $fieldValues);
    $this->assertEntityWithName($value, $fieldName, $entity);
  }

  /**
   * Assert that an entity is created & wrapped with a specified name.
   */
  protected function assertEntityWithName($value, $fieldName, $entity) {
    $value = $this->randomString();
    $fieldName = 'name';

    $field = New DriverFieldDrupal8(
    [['value' => $value]],
    $fieldName,
    $this->entityType
    );

    // Test setFields.
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );
    $entity->setFields([$fieldName => $field]);

    $this->assertTrue($entity->isNew());
    $this->assertEquals($entity->getEntityTypeId(), $this->entityType);
    $this->assertEquals($entity->bundle(), $this->entityType);

    // The generic driverentity plugin should have been discovered and matched.
    // The test plugin has a lower weight, so is ignored.
    $this->assertEquals('generic8', $entity->getFinalPlugin()->getPluginId());

    // Test save method.
    $entity->save();
    // The test driverfield plugin has been matched,  which mutates the text.
    $processedName = 'now' . $value . 'processed';
    $entities = $this->storage->loadByProperties([$fieldName => $processedName]);
    $this->assertEquals(1, count($entities));

    // Test real drupal entity is attached to wrapper.
    $drupalEntity = $entity->getEntity();
    $this->assertTrue($drupalEntity instanceof EntityInterface);
    $this->assertFalse($drupalEntity->isNew());
    $this->assertEquals(array_shift($entities)->id(), $drupalEntity->id());

    $this->assertFalse($entity->isNew());

    // Test calling Drupal entity methods via the wrapper.
    // isDefaultKey comes from ContentEntityBase which entity_test inherits.
    $this->assertTrue($entity->isDefaultRevision());
  }

  /**
   * Test additional driver entity methods.
   */
  public function testSetEntityPlugin() {
    // Test setting entity type and bundle explicitly, not in construct method.
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );

    // Test setEntityPlugin, bypassing normal plugin discovery and matching,
    // instead assigning the 'test' plugin.
    $config = [
      'type' => $this->entityType,
      'bundle' => $this->entityType,
      'fieldPluginManager' => $this->fieldPluginManager
    ];
    // Normally the test plugin is ignored because it is a lower weight than
    // the generic plugin. Test if we can explicitly set it.
    $plugin = $this->entityPluginManager->createInstance('test8', $config);
    $entity->setFinalPlugin($plugin);
    $this->assertEquals('test8', $entity->getFinalPlugin()->getPluginId());
  }

  /**
   * Test create method.
   */
  public function testCreate() {
    $value = $this->randomString();
    $fieldName = 'name';
    $fields = [$fieldName=> [['value' => $value]]];

    // Test create method.
    $entity = DriverEntityDrupal8::create(
      $fields,
      $this->entityType
    )->save();
    // The test driverfield plugin has been matched,  which mutates the text.
    $processedName = 'now' . $value . 'processed';
    $entities = $this->storage->loadByProperties(['name' => $processedName]);
    $this->assertEquals(1, count($entities));
  }

  /**
   * Test identifying entity type by label.
   */
  public function testEntityTypeLabel() {
    $value = $this->randomString();
    $fieldName = 'name';
    $fields = [$fieldName=> [['value' => $value]]];

    // "Test entity" is the label of the entity_test entity type.
    $entity = DriverEntityDrupal8::create(
      $fields,
      "Test entity"
    )->save();
    // The test driverfield plugin has been matched,  which mutates the text.
    $processedName = 'now' . $value . 'processed';
    $entities = $this->storage->loadByProperties(['name' => $processedName]);
    $this->assertEquals(1, count($entities));
  }

  /**
   * Test identifying entity type by machine name without underscores.
   */
  public function testEntityTypeWithoutUnderscores() {
    $value = $this->randomString();
    $fieldName = 'name';
    $fields = [$fieldName=> [['value' => $value]]];

    // Instead of "entity_test", try capitalised and without underscores.
    $entity = DriverEntityDrupal8::create(
      $fields,
      "ENTITY TEST"
    )->save();
    // The test driverfield plugin has been matched,  which mutates the text.
    $processedName = 'now' . $value . 'processed';
    $entities = $this->storage->loadByProperties(['name' => $processedName]);
    $this->assertEquals(1, count($entities));
  }

  /**
   * Replace values in strings or recursively in arrays.
   *
   * @param string $find
   *   The string to be replaced.
   * @param string $replace
   *   The string to replace with.
   * @param array|string $heap
   *   The heap to iterate over.
   *
   * @return array|string
   *   The heap with the strings replaced.
   */
  protected function recursiveReplace($find, $replace, $heap) {
    if (!is_array($heap)) {
      return str_replace($find, $replace, $heap);
    }
    $newArray = [];
    foreach ($heap as $key => $value) {
      $newArray[$key] = $this->recursiveReplace($find, $replace, $value);
    }
    return $newArray;
  }

}


