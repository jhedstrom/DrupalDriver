<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;
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

  /**
   * Test that a term can be created and deleted.
   */
  public function testTermCreateDeleteByWrapper() {
    $name = $this->randomString();
    $fields = [
      'name' => $name,
      'vocabulary' => 'testvocab',
    ];
    $term = DriverEntityDrupal8::create($fields, $this->entityType)->save();

    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(1, count($entities));

    // Check the id of the new term has been added to the returned object.
    $entity = reset($entities);
    $this->assertEquals($entity->id(), $term->tid);

    // Check the term can be deleted.
    $term->delete();
    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(0, count($entities));
  }

  /**
   * Test that a term can be created with a parent term.
   * Also that a vocabulary can be referred to by it label.
   */
  public function testTermCreateWithParentByWrapper() {
    $parentName = $this->randomString();
    $parentFields = [
      'name' => $parentName,
      // Test using label not machine name for vocab reference.
      'vocabulary' => 'test vocabulary',
    ];
    $parent = DriverEntityDrupal8::create($parentFields, $this->entityType)->save();

    $childName = $this->randomString();
    $childFields = [
      'name' => $childName,
      'vocabulary' => 'testvocab',
      'parent' => $parentName,
    ];
    $child = DriverEntityDrupal8::create($childFields, $this->entityType)->save();

    $entities = $this->storage->loadByProperties(['name' => $childName]);
    $this->assertEquals(1, count($entities));

    // Check the parent is set on the child term.
    $entity = reset($entities);
    $parentEntities = $this->storage->loadParents($entity->id());
    $parentEntity = reset($parentEntities);
    $this->assertEquals($parent->tid, $parentEntity->id());

  }

  /**
   * Test 'vocabulary_machine_name' as BC support for old human-friendly name.
   */
  public function testVocabularyBCBundleName() {
    $name = $this->randomString();
    $fields = [
      'name' => $name,
      'vocabulary_machine_name' => 'testvocab',
    ];
    $term = DriverEntityDrupal8::create($fields, $this->entityType)->save();

    $entities = $this->storage->loadByProperties(['name' => $name, 'vid' => 'testvocab']);
    $this->assertEquals(1, count($entities));
  }

}
