<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Hint;

use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Hint\PostCreateHintInterface;
use Drupal\Driver\Hint\RolesHint;
use Drupal\Tests\Driver\Unit\Fixtures\RecordingUserCapability;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the 'RolesHint' creation hint.
 *
 * @group hints
 */
#[Group('hints')]
class RolesHintTest extends TestCase {

  /**
   * Tests metadata accessors.
   */
  public function testMetadataAccessors(): void {
    $hint = new RolesHint(new RecordingUserCapability());

    $this->assertInstanceOf(PostCreateHintInterface::class, $hint);
    $this->assertSame('roles', $hint->getName());
    $this->assertSame('user', $hint->getEntityType());
    $this->assertNotSame('', $hint->getDescription());
  }

  /**
   * Tests that every entry in 'roles' triggers a 'userAddRole()' call.
   */
  public function testApplyAfterCreateAssignsEachRole(): void {
    $driver = new RecordingUserCapability();
    $hint = new RolesHint($driver);

    $stub = new EntityStub('user', NULL, ['name' => 'bob', 'roles' => ['editor', 'reviewer']]);
    $entity = new \stdClass();

    $hint->applyAfterCreate($stub, $entity);

    $this->assertSame(['editor', 'reviewer'], $driver->roles);
  }

  /**
   * Tests that non-array 'roles' values no-op without errors.
   *
   * @param mixed $roles
   *   The 'roles' value placed on the stub.
   */
  #[DataProvider('dataProviderApplyAfterCreateIgnoresNonArrayValues')]
  public function testApplyAfterCreateIgnoresNonArrayValues(mixed $roles): void {
    $driver = new RecordingUserCapability();
    $hint = new RolesHint($driver);

    $stub = new EntityStub('user', NULL, ['name' => 'bob', 'roles' => $roles]);
    $entity = new \stdClass();

    $hint->applyAfterCreate($stub, $entity);

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
    $hint = new RolesHint($driver);

    $stub = new EntityStub('user', NULL, ['name' => 'bob', 'roles' => []]);
    $entity = new \stdClass();

    $hint->applyAfterCreate($stub, $entity);

    $this->assertSame([], $driver->roles);
  }

}
