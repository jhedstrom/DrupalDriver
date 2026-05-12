<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Driver\Capability\CreationHintCapabilityInterface;
use Drupal\Driver\DrushDriver;
use Drupal\Driver\Hint\CreationHintInterface;
use Drupal\Driver\Hint\RolesHint;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests creation-hint discovery on 'DrushDriver'.
 *
 * Drush owns 'RolesHint' (post-create) and nothing else by default.
 *
 * @group drivers
 * @group drush
 * @group hints
 */
#[Group('drivers')]
#[Group('drush')]
#[Group('hints')]
class DrushDriverCreationHintsTest extends TestCase {

  /**
   * Tests that DrushDriver implements the opt-in capability interface.
   */
  public function testImplementsCreationHintCapability(): void {
    $this->assertTrue(is_subclass_of(DrushDriver::class, CreationHintCapabilityInterface::class));
  }

  /**
   * Tests that 'roles' is registered for the user entity type.
   */
  public function testRolesHintRegisteredForUser(): void {
    $driver = new DrushDriver('test-alias');

    $hints = $driver->getCreationHints('user');

    $this->assertArrayHasKey('roles', $hints);
    $this->assertInstanceOf(RolesHint::class, $hints['roles']);
  }

  /**
   * Tests that Drush ships no node/term hints by default.
   */
  public function testNoContentHintsByDefault(): void {
    $driver = new DrushDriver('test-alias');

    $this->assertSame([], $driver->getCreationHints('node'));
    $this->assertSame([], $driver->getCreationHints('taxonomy_term'));
  }

  /**
   * Tests that 'getCreationHints()' returns '[]' for unknown entity types.
   */
  public function testGetCreationHintsReturnsEmptyForUnknown(): void {
    $driver = new DrushDriver('test-alias');

    $this->assertSame([], $driver->getCreationHints('unknown_type'));
  }

  /**
   * Tests that re-registering a hint replaces the previous instance.
   */
  public function testRegisterCreationHintReplacesByName(): void {
    $driver = new DrushDriver('test-alias');

    $replacement = new class implements CreationHintInterface {

      /**
       * {@inheritdoc}
       */
      public function getName(): string {
        return 'roles';
      }

      /**
       * {@inheritdoc}
       */
      public function getEntityType(): string {
        return 'user';
      }

      /**
       * {@inheritdoc}
       */
      public function getDescription(): string {
        return 'Override.';
      }

    };

    $driver->registerCreationHint($replacement);

    $hints = $driver->getCreationHints('user');

    $this->assertSame($replacement, $hints['roles']);
  }

}
