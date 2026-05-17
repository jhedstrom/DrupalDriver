<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Driver\Alias\CreationAliasInterface;
use Drupal\Driver\Alias\RolesAlias;
use Drupal\Driver\Capability\CreationAliasCapabilityInterface;
use Drupal\Driver\DrushDriver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests creation-alias discovery on 'DrushDriver'.
 *
 * Drush owns 'RolesAlias' (post-create) and nothing else by default.
 *
 * @group drivers
 * @group drush
 * @group aliases
 */
#[Group('drivers')]
#[Group('drush')]
#[Group('aliases')]
class DrushDriverCreationAliasesTest extends TestCase {

  /**
   * Tests that DrushDriver implements the opt-in capability interface.
   */
  public function testImplementsCreationAliasCapability(): void {
    $this->assertTrue(is_subclass_of(DrushDriver::class, CreationAliasCapabilityInterface::class));
  }

  /**
   * Tests that 'roles' is registered for the user entity type.
   */
  public function testRolesAliasRegisteredForUser(): void {
    $driver = new DrushDriver('test-alias');

    $aliases = $driver->getCreationAliases('user');

    $this->assertArrayHasKey('roles', $aliases);
    $this->assertInstanceOf(RolesAlias::class, $aliases['roles']);
  }

  /**
   * Tests that Drush ships no node/term aliases by default.
   */
  public function testNoContentAliasesByDefault(): void {
    $driver = new DrushDriver('test-alias');

    $this->assertSame([], $driver->getCreationAliases('node'));
    $this->assertSame([], $driver->getCreationAliases('taxonomy_term'));
  }

  /**
   * Tests that 'getCreationAliases()' returns '[]' for unknown entity types.
   */
  public function testGetCreationAliasesReturnsEmptyForUnknown(): void {
    $driver = new DrushDriver('test-alias');

    $this->assertSame([], $driver->getCreationAliases('unknown_type'));
  }

  /**
   * Tests that re-registering an alias replaces the previous instance.
   */
  public function testRegisterCreationAliasReplacesByName(): void {
    $driver = new DrushDriver('test-alias');

    $replacement = new class implements CreationAliasInterface {

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

    $driver->registerCreationAlias($replacement);

    $aliases = $driver->getCreationAliases('user');

    $this->assertSame($replacement, $aliases['roles']);
  }

}
