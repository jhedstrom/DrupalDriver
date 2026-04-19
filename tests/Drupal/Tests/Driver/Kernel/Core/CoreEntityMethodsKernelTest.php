<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for generic entity methods on Core via the driver.
 *
 * Covers 'entityCreate()', 'entityDelete()' (both the stub-object branch and
 * the loaded-entity branch), and 'expandEntityBaseFields()'.
 *
 * @group core
 */
#[Group('core')]
class CoreEntityMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = ['system', 'user', 'entity_test'];

  /**
   * The Core driver under test.
   */
  protected Core $core;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['system', 'user']);
    $this->core = new Core($this->root);
  }

  /**
   * Tests 'entityCreate()' followed by 'entityDelete()' using a stub object.
   */
  public function testEntityCreateAndDeleteWithStub(): void {
    $stub = (object) [
      'name' => 'zoe',
      'mail' => 'zoe@example.com',
      'status' => 1,
    ];

    $created = $this->core->entityCreate('user', $stub);

    $this->assertInstanceOf(EntityInterface::class, $created);
    $this->assertNotEmpty($stub->id, 'entityCreate set the id on the stub.');

    // Delete via the stub, which triggers the load-by-id branch of
    // entityDelete().
    $this->core->entityDelete('user', $stub);

    $this->assertNull(User::load((int) $stub->id));
  }

  /**
   * Tests 'entityDelete()' when given an already-loaded entity.
   */
  public function testEntityDeleteWithLoadedEntity(): void {
    $entity = User::create([
      'name' => 'taylor',
      'mail' => 'taylor@example.com',
      'status' => 1,
    ]);
    $entity->save();

    $this->core->entityDelete('user', $entity);

    $this->assertNull(User::load((int) $entity->id()));
  }

  /**
   * Tests 'entityCreate()' maps 'step_bundle' onto the real bundle key.
   */
  public function testEntityCreateMapsStepBundle(): void {
    // 'user' entity type has no 'type' bundle key (user has no bundles), so
    // the step_bundle path is exercised via any entity type where the bundle
    // key is not already present on the stub.
    $stub = (object) [
      'name' => 'sam',
      'mail' => 'sam@example.com',
      'status' => 1,
      // 'step_bundle' is ignored for entity types without a bundle key.
      'step_bundle' => 'user',
    ];
    $created = $this->core->entityCreate('user', $stub);

    $this->assertInstanceOf(User::class, $created);
    $this->assertSame('sam', $created->getAccountName());
  }

  /**
   * Tests 'expandEntityBaseFields()' invokes the field handler pipeline.
   */
  public function testExpandEntityBaseFieldsRewritesBaseField(): void {
    $stub = (object) ['name' => 'bobbie'];

    // 'name' is a base field on the user entity type; the driver's
    // DefaultHandler wraps scalar values in a value-array.
    $this->core->expandEntityBaseFields('user', $stub, ['name']);

    $this->assertSame(['bobbie'], $stub->name);
  }

  /**
   * Tests that 'entityCreate()' rejects an unknown bundle for a bundled type.
   *
   * 'entity_test' has a 'type' bundle key but no bundles registered unless
   * explicitly created; any supplied bundle therefore triggers the guard.
   */
  public function testEntityCreateRejectsUnknownBundle(): void {
    $stub = (object) [
      'type' => 'not_a_real_bundle',
      'name' => 'orphan',
    ];

    $this->expectException(\Exception::class);
    $this->expectExceptionMessageMatches("/Cannot create entity because provided bundle 'not_a_real_bundle' does not exist/");

    $this->core->entityCreate('entity_test', $stub);
  }

}
