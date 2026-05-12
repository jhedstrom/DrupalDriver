<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Driver\Capability\CreationHintCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Core\CoreInterface;
use Drupal\Driver\DrupalDriver;
use Drupal\Driver\Hint\RolesHint;
use Drupal\Tests\Driver\Unit\Fixtures\HintCapableCoreInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests creation-hint discovery on 'DrupalDriver'.
 *
 * The driver delegates to its 'Core' instance; behaviour here pins the
 * delegation contract without booting Drupal.
 *
 * @group drivers
 * @group drupal
 * @group hints
 */
#[Group('drivers')]
#[Group('drupal')]
#[Group('hints')]
class DrupalDriverCreationHintsTest extends TestCase {

  /**
   * Tests that DrupalDriver implements the opt-in capability interface.
   */
  public function testImplementsCreationHintCapability(): void {
    $this->assertTrue(is_subclass_of(DrupalDriver::class, CreationHintCapabilityInterface::class));
  }

  /**
   * Tests that 'getCreationHints()' returns '[]' for a non-hint core.
   */
  public function testGetCreationHintsReturnsEmptyForLegacyCore(): void {
    $core = $this->createMock(CoreInterface::class);
    $driver = $this->createDriverWithCore($core);

    $this->assertSame([], $driver->getCreationHints('node'));
  }

  /**
   * Tests that 'getCreationHints()' delegates to a hint-capable core.
   */
  public function testGetCreationHintsDelegatesToCore(): void {
    $hint = new RolesHint($this->createStubUserCapability());
    $hint_capable_core = $this->createMock(HintCapableCoreInterface::class);
    $hint_capable_core->expects($this->once())
      ->method('getCreationHints')
      ->with('user')
      ->willReturn(['roles' => $hint]);

    $driver = $this->createDriverWithCore($hint_capable_core);

    $this->assertSame(['roles' => $hint], $driver->getCreationHints('user'));
  }

  /**
   * Returns a noop 'UserCapabilityInterface' double for RolesHint construction.
   */
  protected function createStubUserCapability(): UserCapabilityInterface {
    return $this->createMock(UserCapabilityInterface::class);
  }

  /**
   * Creates a 'DrupalDriver' with an injected core.
   *
   * Bypasses the constructor (which requires a real Drupal installation)
   * and sets properties directly via reflection.
   */
  protected function createDriverWithCore(CoreInterface $core): DrupalDriver {
    $reflection = new \ReflectionClass(DrupalDriver::class);
    $driver = $reflection->newInstanceWithoutConstructor();

    $reflection->getProperty('drupalRoot')->setValue($driver, __DIR__);
    $reflection->getProperty('uri')->setValue($driver, 'default');
    $reflection->getProperty('version')->setValue($driver, 11);

    $driver->setCore($core);

    return $driver;
  }

}
