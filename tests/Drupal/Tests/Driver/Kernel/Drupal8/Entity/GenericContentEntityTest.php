<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

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

    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(1, count($entities));

    // Check the id of the new comment has been added to the returned object.
    $entity = reset($entities);
    $this->assertObjectHasAttribute('id', $entity_test);
    $this->assertEquals($entity->id(), $entity_test->id);

    // Check the comment can be deleted.
    $this->driver->entityDelete('entity_test', $entity_test);
    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(0, count($entities));
  }

}
