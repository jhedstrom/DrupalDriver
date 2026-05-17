<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Alias;

use Drupal\Driver\Alias\PostCreateAliasInterface;
use Drupal\Driver\Alias\RolesAlias;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Tests\Driver\Unit\Fixtures\RecordingUserCapability;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the 'RolesAlias' creation alias.
 *
 * @group aliases
 */
#[Group('aliases')]
class RolesAliasTest extends TestCase {

  /**
   * Tests metadata accessors.
   */
  public function testMetadataAccessors(): void {
    $alias = new RolesAlias(new RecordingUserCapability());

    $this->assertInstanceOf(PostCreateAliasInterface::class, $alias);
    $this->assertSame('roles', $alias->getName());
    $this->assertSame('user', $alias->getEntityType());
    $this->assertNotSame('', $alias->getDescription());
  }

  /**
   * Tests that every entry in 'roles' triggers a 'userAddRole()' call.
   */
  public function testApplyAfterCreateAssignsEachRole(): void {
    $driver = new RecordingUserCapability();
    $alias = new RolesAlias($driver);

    $stub = new EntityStub('user', NULL, ['name' => 'bob', 'roles' => ['editor', 'reviewer']]);
    $entity = new \stdClass();

    $alias->applyAfterCreate($stub, $entity);

    $this->assertSame(['editor', 'reviewer'], $driver->roles);
  }

  /**
   * Tests that non-array 'roles' values no-op without errors.
   *
   * @param mixed $roles
   *   The 'roles' value placed on the stub.
   *
   * @dataProvider dataProviderApplyAfterCreateIgnoresNonArrayValues
   */
  #[DataProvider('dataProviderApplyAfterCreateIgnoresNonArrayValues')]
  public function testApplyAfterCreateIgnoresNonArrayValues(mixed $roles): void {
    $driver = new RecordingUserCapability();
    $alias = new RolesAlias($driver);

    $stub = new EntityStub('user', NULL, ['name' => 'bob', 'roles' => $roles]);
    $entity = new \stdClass();

    $alias->applyAfterCreate($stub, $entity);

    $this->assertSame([], $driver->roles, 'No role assignment should occur for non-array values.');
  }

  /**
   * Data provider for 'testApplyAfterCreateIgnoresNonArrayValues()'.
   *
   * @return iterable<string, array<int, mixed>>
   *   Cases of non-array 'roles' value.
   */
  public static function dataProviderApplyAfterCreateIgnoresNonArrayValues(): iterable {
    yield 'string' => ['editor'];
    yield 'integer' => [42];
    yield 'null' => [NULL];
    yield 'boolean false' => [FALSE];
  }

  /**
   * Tests that an empty array is iterated zero times.
   */
  public function testApplyAfterCreateNoOpsOnEmptyArray(): void {
    $driver = new RecordingUserCapability();
    $alias = new RolesAlias($driver);

    $stub = new EntityStub('user', NULL, ['name' => 'bob', 'roles' => []]);
    $entity = new \stdClass();

    $alias->applyAfterCreate($stub, $entity);

    $this->assertSame([], $driver->roles);
  }

}
