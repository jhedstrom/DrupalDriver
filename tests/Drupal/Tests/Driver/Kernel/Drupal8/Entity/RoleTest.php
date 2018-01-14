<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\Tests\Driver\Kernel\Drupal8\Entity\DriverEntityKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;

/**
 * Tests the driver's handling of role entities.
 *
 * @group driver
 */
class RoleTest extends DriverEntityKernelTestBase
{

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
    protected $entityType = 'user_role';

  /**
   * Our entity is a config entity.
   *
   * @boolean
   */
    protected $config = true;

  /**
   * Test that a role can be created and deleted.
   */
    public function testRoleCreateDelete()
    {

        $permissions = [
        'view the administration theme',
        ];
        $roleName = $this->driver->roleCreate($permissions);
        $role = Role::load($roleName);
        $this->assertNotNull($role);
        $this->assertEquals($permissions, $role->getPermissions());

        // Check the role can be deleted.
        $this->driver->roleDelete($roleName);
        $role = Role::load($roleName);
        $this->assertNull($role);
    }

  /**
   * Test that a role can be created and deleted.
   */
    public function testRoleCreateDeleteNew()
    {
        $name = $this->randomMachineName();
        $permissions = [
        'view the administration theme',
        ];
        $entity = new DriverEntityDrupal8(
            $this->entityType
        );
        $entity->set('id', $name);
        $entity->set('permissions', $permissions);
        $entity->save();

        $role = Role::load($name);
        $this->assertNotNull($role);
        $this->assertEquals($permissions, $role->getPermissions());

        // Check the role can be deleted.
        $entity->delete();
        $role = Role::load($name);
        $this->assertNull($role);
    }

  /**
   * Test that an exception is thrown if config property is missing.
   */
    public function testMissingConfigProperty()
    {
        $name = $this->randomString();
        $entity = new DriverEntityDrupal8(
            $this->entityType
        );
        $this->setExpectedException(\Exception::class, "Field or property cannot be identified");
        $entity->set('nonexistentproperty', $name);
    }
}
