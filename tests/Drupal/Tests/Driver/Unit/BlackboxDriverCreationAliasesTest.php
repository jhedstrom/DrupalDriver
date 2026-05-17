<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Driver\BlackboxDriver;
use Drupal\Driver\Capability\CreationAliasCapabilityInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests that 'BlackboxDriver' opts out of the creation-alias capability.
 *
 * Consistent with the driver's no-capability stance, it does not
 * implement 'CreationAliasCapabilityInterface' and consumers must
 * 'instanceof'-check before calling 'getCreationAliases()'.
 *
 * @group drivers
 * @group blackbox
 * @group aliases
 */
#[Group('drivers')]
#[Group('blackbox')]
#[Group('aliases')]
class BlackboxDriverCreationAliasesTest extends TestCase {

  /**
   * Tests that BlackboxDriver does NOT implement the capability.
   */
  public function testDoesNotImplementCreationAliasCapability(): void {
    $this->assertFalse(is_subclass_of(BlackboxDriver::class, CreationAliasCapabilityInterface::class));

    $driver = new BlackboxDriver();
    $this->assertNotInstanceOf(CreationAliasCapabilityInterface::class, $driver);
  }

}
