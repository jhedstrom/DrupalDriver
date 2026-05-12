<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Driver\Core\Core;
use Drupal\Driver\Entity\EntityStub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for user-related methods on Core.
 *
 * The whole lifecycle runs inside one test method on purpose: KernelTestBase's
 * setUp runs per-method and costs roughly a second of bootstrap. Bundling
 * closely related assertions here keeps CI time down without sacrificing
 * coverage. Split a method out only when a scenario genuinely needs its own
 * clean state (e.g. asserting a failure path that leaves the container dirty).
 *
 * To actually run this test, see the bootstrap/env notes in
 * DatetimeHandlerKernelTest.
 *
 * @group core
 */
#[Group('core')]
class CoreUserMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    'system',
    'user',
  ];

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
    // users_data is used by user_cancel's batch callback; required for
    // synchronous userDelete to complete without hitting a missing table.
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['user']);

    // Core's bootstrap() is NOT called - KernelTestBase has already booted the
    // kernel. We just instantiate and exercise the API methods directly.
    $this->core = new Core($this->root);
  }

  /**
   * Tests the full user/role lifecycle in one bundled method.
   *
   * Rationale: every assertion below shares the same module set and a clean
   * user table; running them as separate test methods would multiply the
   * ~1s setUp cost four-fold. The sequence also mirrors how the driver is
   * used in practice - create user, mint role, grant role, tear down.
   */
  public function testUserLifecycle(): void {
    // 1. userCreate assigns a UID and persists the account.
    $user_stub = new EntityStub('user', NULL, [
      'name' => 'alice',
      'mail' => 'alice@example.com',
      'pass' => 'correcthorsebatterystaple',
    ]);
    $this->core->userCreate($user_stub);

    $this->assertNotEmpty($user_stub->getValue('uid'), 'userCreate populated uid.');
    $this->assertTrue($user_stub->isSaved(), 'userCreate marked the stub saved.');
    $account = User::load($user_stub->getValue('uid'));
    $this->assertInstanceOf(User::class, $account);
    $this->assertSame('alice', $account->getAccountName());
    $this->assertSame(1, (int) $account->get('status')->value);

    // 2. roleCreate returns a new role id and stores the granted permissions.
    // 'access user profiles' is provided by the user module enabled here, so
    // checkPermissions() can validate it in isolation without pulling in node.
    $permission = 'access user profiles';
    $role_id = $this->core->roleCreate([$permission]);
    $this->assertIsString($role_id);
    $role = Role::load($role_id);
    $this->assertInstanceOf(Role::class, $role);
    $this->assertTrue($role->hasPermission($permission));

    // 3. userAddRole attaches the role to the user.
    $this->core->userAddRole($user_stub, $role_id);
    $account = User::load($user_stub->getValue('uid'));
    $this->assertContains($role_id, $account->getRoles());

    // 4. userDelete removes the user (processes the batch synchronously).
    $this->core->userDelete($user_stub);
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($user_stub->getValue('uid')));

    // 5. roleDelete removes the role.
    $this->core->roleDelete($role_id);
    $this->assertNull(Role::load($role_id));
  }

  /**
   * Tests that 'userAddRole()' throws when the role name is unknown.
   */
  public function testUserAddRoleThrowsOnUnknownRole(): void {
    $stub = new EntityStub('user', NULL, [
      'name' => 'ghost',
      'mail' => 'ghost@example.com',
      'pass' => 'pw',
    ]);
    $this->core->userCreate($stub);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/No role "nonexistent-role" exists/');

    $this->core->userAddRole($stub, 'nonexistent-role');
  }

  /**
   * Tests that roleCreate rejects unknown permission strings.
   *
   * Kept as a separate method because it exercises a failure path - better
   * to run it in a clean container than tacked onto the happy path above.
   */
  public function testRoleCreateRejectsUnknownPermission(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid permission "definitely not a real permission"');

    $this->core->roleCreate(['definitely not a real permission']);
  }

  /**
   * Tests 'roleCreate()' honours explicit id and label arguments.
   *
   * Without this path, scenarios that need to assert against a role by name
   * (e.g. verifying it appears in a permissions admin form) have to scrape the
   * random id out of the return value - which makes the assertion opaque.
   */
  public function testRoleCreateAcceptsExplicitIdAndLabel(): void {
    $role_id = $this->core->roleCreate(['access user profiles'], 'editor', 'Editor');

    $this->assertSame('editor', $role_id);
    $role = Role::load('editor');
    $this->assertInstanceOf(Role::class, $role);
    $this->assertSame('Editor', $role->label());
    $this->assertTrue($role->hasPermission('access user profiles'));
  }

  /**
   * Tests 'roleCreate()' falls back to the id as label when only id is given.
   */
  public function testRoleCreateFallsBackToIdAsLabel(): void {
    $role_id = $this->core->roleCreate([], 'content_editor');

    $this->assertSame('content_editor', $role_id);
    $role = Role::load('content_editor');
    $this->assertInstanceOf(Role::class, $role);
    $this->assertSame('content_editor', $role->label());
  }

  /**
   * Tests that 'userCreate()' honours the 'roles' creation hint.
   *
   * Mirrors the existing Drush-side test (DrushDriverMethodsTest) on Core
   * and closes the symmetry gap: a stub created via Core can now assign
   * roles in one call instead of requiring a follow-up 'userAddRole()'.
   */
  public function testUserCreateAppliesRolesHint(): void {
    $role_id = $this->core->roleCreate(['access user profiles'], 'editor');

    $stub = new EntityStub('user', NULL, [
      'name' => 'roleuser',
      'mail' => 'role@example.com',
      'pass' => 'pw',
      'roles' => [$role_id],
    ]);

    $this->core->userCreate($stub);

    $account = User::load($stub->getValue('uid'));
    $this->assertInstanceOf(User::class, $account);
    $this->assertContains($role_id, $account->getRoles());
  }

}
