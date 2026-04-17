<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver;

use Drupal\Driver\Core\Core;
use PHPUnit\Framework\TestCase;

/**
 * Tests permission label and machine name conversion in the Drupal 8+ driver.
 */
class Drupal8PermissionsTest extends TestCase {

  /**
   * Tests that human-readable titles are converted to machine names.
   *
   * Drupal returns permission titles as TranslatableMarkup objects. Strict
   * comparison against plain string labels would never match, so the driver
   * must cast the title to string before lookup. This guards against
   * regressions where automated refactoring flips the comparison to strict
   * mode without adding the cast.
   */
  public function testConvertPermissionsMapsStringableTitlesToMachineNames(): void {
    $core = new TestDrupal8PermissionsCore(__DIR__, 'default');
    $core->setPermissions([
      'administer content types' => [
        'title' => $this->stringable('Administer content types'),
      ],
      'administer users' => [
        'title' => $this->stringable('Administer users'),
      ],
    ]);

    $permissions = ['Administer content types', 'Administer users'];
    $this->callConvertPermissions($core, $permissions);

    $this->assertSame(['administer content types', 'administer users'], $permissions);
  }

  /**
   * Tests that titles already in machine name form are left unchanged.
   */
  public function testConvertPermissionsLeavesMachineNamesAlone(): void {
    $core = new TestDrupal8PermissionsCore(__DIR__, 'default');
    $core->setPermissions([
      'administer users' => [
        'title' => $this->stringable('Administer users'),
      ],
    ]);

    $permissions = ['administer users'];
    $this->callConvertPermissions($core, $permissions);

    $this->assertSame(['administer users'], $permissions);
  }

  /**
   * Tests that 'checkPermissions()' passes for valid machine names.
   */
  public function testCheckPermissionsAcceptsValidMachineNames(): void {
    $core = new TestDrupal8PermissionsCore(__DIR__, 'default');
    $core->setPermissions([
      'administer users' => ['title' => 'Administer users'],
      'access content' => ['title' => 'Access content'],
    ]);

    $permissions = ['administer users', 'access content'];
    $this->callCheckPermissions($core, $permissions);

    $this->assertSame(['administer users', 'access content'], $permissions);
  }

  /**
   * Tests that 'checkPermissions()' throws for unknown machine names.
   */
  public function testCheckPermissionsThrowsForUnknownPermission(): void {
    $core = new TestDrupal8PermissionsCore(__DIR__, 'default');
    $core->setPermissions([
      'administer users' => ['title' => 'Administer users'],
    ]);

    $permissions = ['administer users', 'unknown permission'];

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid permission "unknown permission".');

    $this->callCheckPermissions($core, $permissions);
  }

  /**
   * Invokes the protected 'convertPermissions()' method by reference.
   *
   * @param \Drupal\Driver\Core\Core $core
   *   The core instance to invoke the method on.
   * @param array<string> &$permissions
   *   The permissions array to convert.
   */
  protected function callConvertPermissions(Core $core, array &$permissions): void {
    $method = new \ReflectionMethod($core, 'convertPermissions');
    $method->invokeArgs($core, [&$permissions]);
  }

  /**
   * Invokes the protected 'checkPermissions()' method by reference.
   *
   * @param \Drupal\Driver\Core\Core $core
   *   The core instance to invoke the method on.
   * @param array<string> &$permissions
   *   The permissions array to check.
   */
  protected function callCheckPermissions(Core $core, array &$permissions): void {
    $method = new \ReflectionMethod($core, 'checkPermissions');
    $method->invokeArgs($core, [&$permissions]);
  }

  /**
   * Returns an anonymous Stringable that mimics a Drupal TranslatableMarkup.
   */
  protected function stringable(string $label): object {
    return new class($label) {

      public function __construct(private readonly string $label) {}

      /**
       * Renders the stringable into its label.
       */
      public function __toString(): string {
        return $this->label;
      }

    };
  }

}

/**
 * Testable subclass that overrides 'getAllPermissions()'.
 */
class TestDrupal8PermissionsCore extends Core {

  /**
   * Stored permissions keyed by machine name.
   *
   * @var array<string, mixed>
   */
  protected array $permissions = [];

  /**
   * Sets the permissions returned by 'getAllPermissions()'.
   *
   * @param array<string, mixed> $permissions
   *   The permissions to set.
   */
  public function setPermissions(array $permissions): void {
    $this->permissions = $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAllPermissions(): array {
    return $this->permissions;
  }

}
