<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\Tests\Driver\Kernel\Drupal8\Entity\DriverEntityKernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests the driver's handling of term entities.
 *
 * @group driver
 */
class TaxonomyTermTest extends DriverEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy',];

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
  protected $entityType = 'taxonomy_term';

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $vocabulary = Vocabulary::create(['vid' => 'testvocab', 'name' => 'test vocabulary']);
    $vocabulary->save();
  }

  /**
   * Test that a term can be created and deleted.
   */
  public function testTermCreateDelete() {
    $name = $this->randomString();
    $term = (object) [
      'name' => $name,
      'vocabulary_machine_name' => 'testvocab',
    ];
    $term = $this->driver->createTerm($term);

    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(1, count($entities));

    // Check the id of the new term has been added to the returned object.
    $entity = reset($entities);
    $this->assertObjectHasAttribute('tid', $term);
    $this->assertEquals($entity->id(), $term->tid);

    // Check the term can be deleted.
    $this->driver->termDelete($term);
    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(0, count($entities));
  }

  /**
   * Test that a term can be created with a parent term.
   */
  public function testTermCreateWithParent() {
    $parentName = $this->randomString();
    $parent = (object) [
      'name' => $parentName,
      'vocabulary_machine_name' => 'testvocab',
    ];
    $parent = $this->driver->createTerm($parent);

    $childName = $this->randomString();
    $child = (object) [
      'name' => $childName,
      'vocabulary_machine_name' => 'testvocab',
      'parent' => $parentName,
    ];
    $child = $this->driver->createTerm($child);

    $entities = $this->storage->loadByProperties(['name' => $childName]);
    $this->assertEquals(1, count($entities));

    // Check the parent is set on the child term.
    $entity = reset($entities);
    $parentEntities = $this->storage->loadParents($entity->id());
    $parentEntity = reset($parentEntities);
    $this->assertEquals($parent->tid, $parentEntity->id());

  }

}
