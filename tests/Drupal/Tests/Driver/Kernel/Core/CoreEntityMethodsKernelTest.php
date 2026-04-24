<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Driver\Core\Core;
use Drupal\entity_test\EntityTestHelper;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for generic entity methods on Core via the driver.
 *
 * Covers 'entityCreate()' and 'entityDelete()' (both the stub-object branch
 * and the loaded-entity branch). Base-field expansion is exercised
 * implicitly by any 'entityCreate()' call whose stub sets a base field.
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
    $this->installEntitySchema('entity_test');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['system', 'user']);
    $this->core = new Core($this->root);
  }

  /**
   * Tests 'entityCreate()' followed by 'entityDelete()' using a stub object.
   *
   * The user entity type's id key is 'uid', so entityCreate should populate
   * the stub under 'uid' (not the generic 'id' property), and entityDelete
   * should load by that same key. This matches the convention already used
   * by nodeCreate/nodeDelete (nid), userCreate (uid), and termCreate/
   * termDelete (tid).
   */
  public function testEntityCreateAndDeleteWithStub(): void {
    $stub = (object) [
      'name' => 'zoe',
      'mail' => 'zoe@example.com',
      'status' => 1,
    ];

    $created = $this->core->entityCreate('user', $stub);

    $this->assertInstanceOf(EntityInterface::class, $created);
    $this->assertNotEmpty($stub->uid, 'entityCreate populated the entity type id key (uid) on the stub.');
    $this->assertFalse(property_exists($stub, 'id'), 'entityCreate did not populate a generic "id" property on the stub.');

    // Delete via the stub, which triggers the load-by-id branch of
    // entityDelete() resolved against the entity type id key.
    $this->core->entityDelete('user', $stub);

    $this->assertNull(User::load((int) $stub->uid));
  }

  /**
   * Tests 'entityCreate()' auto-expands base fields set on the stub.
   *
   * 'name' is a base field on the user entity type. Base fields are not
   * registered field storage configs, so without auto-detection the field
   * handler pipeline would skip them and values like entity references on
   * a base field (e.g. 'commerce_product.variations', 'user.roles') would
   * reach entity storage in their raw scalar form. With auto-detection,
   * DefaultHandler wraps the scalar value into the array form expected by
   * the field API - observable here by inspecting the stub after create.
   */
  public function testEntityCreateAutoExpandsBaseFieldsSetOnStub(): void {
    $stub = (object) [
      'name' => 'uma',
      'mail' => 'uma@example.com',
      'status' => 1,
    ];

    $this->core->entityCreate('user', $stub);

    $this->assertSame(['uma'], $stub->name, 'base field "name" was routed through the handler pipeline.');
  }

  /**
   * Tests 'entityDelete()' rejects a stub missing the resolved id key.
   */
  public function testEntityDeleteRejectsStubMissingIdKey(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/stub without the id key "uid" set/');

    $this->core->entityDelete('user', (object) ['name' => 'missing-uid']);
  }

  /**
   * Tests base entity-reference fields round-trip through entityCreate().
   *
   * 'user.roles' is a base entity_reference field targeting the user_role
   * config entity type - structurally the same scenario that motivated the
   * fix (a stub sets a base entity-reference field by label/id and expects
   * the driver to resolve and attach it). Before the fix, base entity-ref
   * fields set on a stub were filtered out of the handler pipeline and
   * never reached EntityReferenceHandler, so the reference was silently
   * dropped. This test pins the end-to-end round-trip: stub -> driver ->
   * storage -> reload -> assertion.
   */
  public function testEntityCreateExpandsBaseEntityReferenceFieldOnStub(): void {
    Role::create(['id' => 'editor', 'label' => 'Editor'])->save();

    $stub = (object) [
      'name' => 'vic',
      'mail' => 'vic@example.com',
      'status' => 1,
      'roles' => ['editor'],
    ];

    $this->core->entityCreate('user', $stub);

    $account = User::load((int) $stub->uid);
    $this->assertInstanceOf(User::class, $account);
    $this->assertContains('editor', $account->getRoles(), 'entityCreate routed user.roles through EntityReferenceHandler for base-field expansion.');
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
   *
   * 'entity_test' has a 'type' bundle key, so the stub's 'step_bundle'
   * should be promoted to 'type' before the entity is saved.
   */
  public function testEntityCreateMapsStepBundle(): void {
    // Feature-detect the helper the same way the field-handler base does:
    // Drupal 11.2+ ships EntityTestHelper; older cores only expose the
    // procedural helper.
    if (class_exists(EntityTestHelper::class)) {
      EntityTestHelper::createBundle('custom_bundle');
    }
    else {
      entity_test_create_bundle('custom_bundle');
    }

    $stub = (object) [
      'name' => 'sam',
      'step_bundle' => 'custom_bundle',
    ];
    $created = $this->core->entityCreate('entity_test', $stub);

    $this->assertSame('custom_bundle', $stub->type, 'step_bundle was promoted to the bundle key.');
    $this->assertSame('custom_bundle', $created->bundle());
  }

  /**
   * Tests 'entityCreate()' rejects an unknown entity type with a clear message.
   *
   * Drupal's 'EntityTypeManager::getDefinition()' raises a
   * 'PluginNotFoundException' with plugin-system vocabulary that does not
   * describe what a scenario author actually did wrong. The driver wraps it
   * as an 'InvalidArgumentException' that names the offending entity type.
   */
  public function testEntityCreateRejectsUnknownEntityType(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/Unknown entity type "nonexistent_type"/');

    $this->core->entityCreate('nonexistent_type', (object) ['name' => 'foo']);
  }

  /**
   * Tests 'entityDelete()' rejects an unknown entity type with a clear message.
   */
  public function testEntityDeleteRejectsUnknownEntityType(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/Unknown entity type "nonexistent_type"/');

    $this->core->entityDelete('nonexistent_type', (object) ['id' => 1]);
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
