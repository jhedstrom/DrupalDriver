<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Driver\Core\Core;
use Drupal\Driver\Entity\EntityStub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for Core::login() and Core::logout().
 *
 * @group core
 */
#[Group('core')]
class CoreAuthenticationMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = ['system', 'user'];

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
    $this->installConfig(['user']);
    // Anonymous user (uid 0) - Drupal expects it to exist.
    User::create([
      'uid' => 0,
      'name' => '',
      'status' => 0,
    ])->save();

    $this->core = new Core($this->root);
  }

  /**
   * Tests that 'login()' switches the active account.
   */
  public function testLoginSwitchesAccount(): void {
    $account = User::create([
      'name' => 'alice',
      'mail' => 'alice@example.com',
      'status' => 1,
    ]);
    $account->save();

    $user_stub = new EntityStub('user', NULL, ['uid' => $account->id()]);
    $this->core->login($user_stub);

    $this->assertSame((int) $account->id(), (int) \Drupal::currentUser()->id());
  }

  /**
   * Tests that 'logout()' pops every switched-in account.
   */
  public function testLogoutRestoresOriginalAccount(): void {
    $original_uid = (int) \Drupal::currentUser()->id();

    $account = User::create([
      'name' => 'bob',
      'mail' => 'bob@example.com',
      'status' => 1,
    ]);
    $account->save();

    $this->core->login(new EntityStub('user', NULL, ['uid' => $account->id()]));
    $this->core->logout();

    $this->assertSame($original_uid, (int) \Drupal::currentUser()->id());
  }

}
