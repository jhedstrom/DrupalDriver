<?php

namespace Drupal\Tests\Driver;

use Drupal\Driver\Cores\Drupal8;
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
  public function testConvertPermissionsMapsStringableTitlesToMachineNames() {
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
  public function testConvertPermissionsLeavesMachineNamesAlone() {
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
  public function testCheckPermissionsAcceptsValidMachineNames() {
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
  public function testCheckPermissionsThrowsForUnknownPermission() {
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
   */
  protected function callConvertPermissions(Drupal8 $core, array &$permissions) {
    $method = new \ReflectionMethod($core, 'convertPermissions');
    $method->setAccessible(TRUE);
    $method->invokeArgs($core, [&$permissions]);
  }

  /**
   * Invokes the protected 'checkPermissions()' method by reference.
   */
  protected function callCheckPermissions(Drupal8 $core, array &$permissions) {
    $method = new \ReflectionMethod($core, 'checkPermissions');
    $method->setAccessible(TRUE);
    $method->invokeArgs($core, [&$permissions]);
  }

  /**
   * Returns an anonymous Stringable that mimics a Drupal TranslatableMarkup.
   */
  protected function stringable($label) {
    return new class($label) {

      public function __construct(private $label) {}

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
class TestDrupal8PermissionsCore extends Drupal8 {

  /**
   * Stored permissions keyed by machine name.
   *
   * @var array
   */
  protected $permissions = [];

  /**
   * Sets the permissions returned by 'getAllPermissions()'.
   */
  public function setPermissions(array $permissions) {
    $this->permissions = $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAllPermissions() {
    return $this->permissions;
  }

}
