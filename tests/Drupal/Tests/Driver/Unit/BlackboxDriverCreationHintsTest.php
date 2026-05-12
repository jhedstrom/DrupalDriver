<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Driver\BlackboxDriver;
use Drupal\Driver\Capability\CreationHintCapabilityInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests that 'BlackboxDriver' opts out of the creation-hint capability.
 *
 * Consistent with the driver's no-capability stance, it does not
 * implement 'CreationHintCapabilityInterface' and consumers must
 * 'instanceof'-check before calling 'getCreationHints()'.
 *
 * @group drivers
 * @group blackbox
 * @group hints
 */
#[Group('drivers')]
#[Group('blackbox')]
#[Group('hints')]
class BlackboxDriverCreationHintsTest extends TestCase {

  /**
   * Tests that BlackboxDriver does NOT implement the capability.
   */
  public function testDoesNotImplementCreationHintCapability(): void {
    $this->assertFalse(is_subclass_of(BlackboxDriver::class, CreationHintCapabilityInterface::class));

    $driver = new BlackboxDriver();
    $this->assertNotInstanceOf(CreationHintCapabilityInterface::class, $driver);
  }

}
