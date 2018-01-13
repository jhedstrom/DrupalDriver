<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;
use Drupal\Tests\Driver\Kernel\Drupal8\Entity\DriverEntityKernelTestBase;

/**
 * Tests the driver's handling of generic content entities using 'entity_test'.
 * We provide no specific handling for this entity type, so this tests the
 * fallback handling for generic content entities.
 *
 * @group Driver
 */
class GenericContentEntityTest extends DriverEntityKernelTestBase {

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
  protected $entityType = 'entity_test';

  /**
   * Test that an entity_test can be created and deleted.
   */
  public function testEntityTestCreateDelete() {
    $name = $this->randomString();
    $entity_test = (object) [
      'name' => $name,
    ];
    $entity_test = $this->driver->createEntity('entity_test', $entity_test);

    // Because of a peculiarity of the old expandEntityFields implementation
    // it did not load the driver field plugin on entity_test's 'name'
    // field, and so does not store the processed value of name.
    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(1, count($entities));

    // Check the id of the new entity has been added to the returned object.
    $entity = reset($entities);
    $this->assertEquals($entity->id(), $entity_test->id);

    // Check the entity can be deleted.
    $this->driver->entityDelete('entity_test', $entity_test);
    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(0, count($entities));
  }

  /**
   * Test that an entity_test can be created and deleted.
   */
  public function testEntityTestCreateDeleteByWrapper() {
    $name = $this->randomString();
    $fields = [
      'name' => [$name],
    ];
    $entity_test = DriverEntityDrupal8::create($fields, $this->entityType)->save();

    // The test driverfield plugin has been matched,  which mutates the text.
    $processedName = 'now' . $name . 'processed';
    $entities = $this->storage->loadByProperties(['name' => $processedName]);
    $this->assertEquals(1, count($entities));

    // Check the id of the new entity has been added to the returned object.
    $entity = reset($entities);
    $this->assertEquals($entity->id(), $entity_test->id);

    // Check the entity can be deleted.
    $entity_test->delete();
    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(0, count($entities));
  }

}
