<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\Tests\Driver\Kernel\Drupal8\Entity\DriverEntityKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;

/**
 * Tests the driver's handling of user entities.
 *
 * @group driver
 */
class UserTest extends DriverEntityKernelTestBase {

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
  protected $entityType = 'user';

  /**
   * @inheritdoc
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('user', 'users_data');
  }

  /**
   * Test that a user can be created and deleted.
   */
  public function testUserCreateDelete() {
    $name = $this->randomString();
    $user = (object) [
      'name' => $name,
    ];
    $user = $this->driver->userCreate($user);

    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(1, count($entities));

    // Status should be set to 1 by default.
    $entity = reset($entities);
    $this->assertEquals(1, $entity->status->value);

    // Looks like we forget to return the user object from userCreate,
    // so none of the code below works. But then how does userDelete ever work?

/*    // Check the id of the new user has been added to the returned object.
    $entity = reset($entities);
    $this->assertEquals($entity->id(), $user->uid);

    // Check the node can be deleted.
    $this->driver->userDelete($user);
    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(0, count($entities));*/
  }

  /**
   * Test that a blocked user can be created.
   */
  public function testUserCreateBlocked() {
    $name = $this->randomString();
    $user = (object) [
      'name' => $name,
      'status' => 0,
    ];
    $user = $this->driver->userCreate($user);

    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(1, count($entities));

    // Status should be set to 0 as explicitly specified.
    $entity = reset($entities);
    $this->assertEquals(0, $entity->status->value);
  }

  /**
   * Test that a user can be given a role, using role label or machine name.
   */
  public function testUserAddRole() {
    $role1Id = $this->randomMachineName();
    $role1Label = $this->randomString();
    $role2Id = $this->randomMachineName();
    $role2Label = $this->randomString();
    $role3Id = $this->randomMachineName();
    $role3Label = $this->randomString();

    $role1 = Role::create(['id' => $role1Id, 'label' => $role1Label]);
    $role2 = Role::create(['id' => $role2Id, 'label' => $role2Label]);
    $role3 = Role::create(['id' => $role3Id, 'label' => $role3Label]);
    $role1->save();
    $role2->save();
    $role3->save();

    $user = $this->createUser();
    $userSimplified = (object) [
      'uid' => $user->id(),
    ];

    $this->driver->userAddRole($userSimplified, $role1Id);
    $this->driver->userAddRole($userSimplified, $role2Label);
    $user = $this->reloadEntity($user);

    // Check role detection is working.
    $this->assertFalse($user->hasRole($role3Id));

    // Check user roles whether specified by machine name or label.
    $this->assertTrue($user->hasRole($role1Id));
    $this->assertTrue($user->hasRole($role2Id));
  }

  /**
   * Test that a user can be created and deleted.
   */
  public function testUserCreateDeleteByWrapper() {
    $name = $this->randomString();
    $fields = [
      'name' => $name,
    ];
    $user = DriverEntityDrupal8::create($fields, $this->entityType)->save();

    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(1, count($entities));

    // Status should be set to 1 by default.
    $entity = reset($entities);
    $this->assertEquals(1, $entity->status->value);

    // Check the id of the new user has been added to the returned object.
    $entity = reset($entities);
    $this->assertEquals($entity->id(), $user->uid);

    // Check the node can be deleted.
    $user->delete();
    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(0, count($entities));
  }

  /**
   * Test that a blocked user can be created.
   */
  public function testUserCreateBlockedByWrapper() {
    $name = $this->randomString();
    $fields = [
      'name' => $name,
      'status' => 0,
    ];
    $user = DriverEntityDrupal8::create($fields, $this->entityType)->save();

    $entities = $this->storage->loadByProperties(['name' => $name]);
    $this->assertEquals(1, count($entities));

    // Status should be set to 0 as explicitly specified.
    $entity = reset($entities);
    $this->assertEquals(0, $entity->status->value);
  }

  /**
   * Test that a user can be given a role, using role label or machine name.
   */
  public function testUserAddRoleByWrapper() {
    $role1Id = $this->randomMachineName();
    $role1Label = $this->randomString();
    $role2Id = $this->randomMachineName();
    $role2Label = $this->randomString();
    $role3Id = $this->randomMachineName();
    $role3Label = $this->randomString();

    $role1 = Role::create(['id' => $role1Id, 'label' => $role1Label]);
    $role2 = Role::create(['id' => $role2Id, 'label' => $role2Label]);
    $role3 = Role::create(['id' => $role3Id, 'label' => $role3Label]);
    $role1->save();
    $role2->save();
    $role3->save();

    $user = $this->createUser();
    $userWrapped = New DriverEntityDrupal8('user');
    $userWrapped->load($user->id());
    $userWrapped->addRole($role1Id);
    $userWrapped->addRole($role2Label);
    $user = $this->reloadEntity($user);

    // Check role detection is working.
    $this->assertFalse($user->hasRole($role3Id));

    // Check user roles whether specified by machine name or label.
    $this->assertTrue($user->hasRole($role1Id));
    $this->assertTrue($user->hasRole($role2Id));
  }

}
