<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Fixtures;

use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Recording test double for 'UserCapabilityInterface'.
 *
 * Used by hint tests that need to assert which roles were assigned to a
 * user without booting a real driver. Calls to 'userCreate()' and
 * 'userDelete()' are intentional no-ops; only 'userAddRole()' records.
 */
class RecordingUserCapability implements UserCapabilityInterface {

  /**
   * Roles assigned during the test, in the order they were applied.
   *
   * @var array<int, string>
   */
  public array $roles = [];

  /**
   * {@inheritdoc}
   */
  public function userCreate(EntityStubInterface $stub): void {
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(EntityStubInterface $stub): void {
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(EntityStubInterface $stub, string $role): void {
    $this->roles[] = $role;
  }

}
