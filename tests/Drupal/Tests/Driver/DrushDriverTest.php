<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver;

use Drupal\Driver\Capability\AuthenticationCapabilityInterface;
use Drupal\Driver\Capability\CacheCapabilityInterface;
use Drupal\Driver\Capability\ConfigCapabilityInterface;
use Drupal\Driver\Capability\ContentCapabilityInterface;
use Drupal\Driver\Capability\FieldCapabilityInterface;
use Drupal\Driver\Capability\LanguageCapabilityInterface;
use Drupal\Driver\Capability\MailCapabilityInterface;
use Drupal\Driver\Capability\ModuleCapabilityInterface;
use Drupal\Driver\Capability\RoleCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Capability\WatchdogCapabilityInterface;
use Drupal\Driver\DriverInterface;
use Drupal\Driver\DrushDriver;
use Drupal\Driver\DrushDriverInterface;
use Drupal\Driver\Exception\BootstrapException;
use Drupal\Driver\SubDriverFinderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Drush driver.
 */
class DrushDriverTest extends TestCase {

  /**
   * Tests that DrushDriver implements its composite contract.
   */
  public function testImplementsDrushDriverInterface(): void {
    $this->assertTrue(is_subclass_of(DrushDriver::class, DrushDriverInterface::class));
    $this->assertTrue(is_subclass_of(DrushDriver::class, DriverInterface::class));
  }

  /**
   * Tests that DrushDriver claims the capabilities Drush genuinely supports.
   *
   * @param string $capability_class
   *   The capability interface name.
   *
   * @dataProvider dataProviderSupportedCapabilities
   */
  public function testImplementsSupportedCapability(string $capability_class): void {
    $this->assertTrue(is_subclass_of(DrushDriver::class, $capability_class), sprintf(
      'DrushDriver must implement %s.',
      $capability_class
    ));
  }

  /**
   * Tests that DrushDriver does not claim capabilities Drush cannot support.
   *
   * @param string $capability_class
   *   The capability interface name.
   *
   * @dataProvider dataProviderUnsupportedCapabilities
   */
  public function testDoesNotImplementUnsupportedCapability(string $capability_class): void {
    $this->assertFalse(is_subclass_of(DrushDriver::class, $capability_class), sprintf(
      'DrushDriver must not implement %s.',
      $capability_class
    ));
  }

  /**
   * Capabilities DrushDriver is expected to support via Drush shell-outs.
   */
  public static function dataProviderSupportedCapabilities(): \Iterator {
    yield 'cache' => [CacheCapabilityInterface::class];
    yield 'config' => [ConfigCapabilityInterface::class];
    yield 'content' => [ContentCapabilityInterface::class];
    yield 'field' => [FieldCapabilityInterface::class];
    yield 'module' => [ModuleCapabilityInterface::class];
    yield 'role' => [RoleCapabilityInterface::class];
    yield 'user' => [UserCapabilityInterface::class];
    yield 'watchdog' => [WatchdogCapabilityInterface::class];
  }

  /**
   * Capabilities DrushDriver cannot support.
   */
  public static function dataProviderUnsupportedCapabilities(): \Iterator {
    yield 'authentication' => [AuthenticationCapabilityInterface::class];
    yield 'language' => [LanguageCapabilityInterface::class];
    yield 'mail' => [MailCapabilityInterface::class];
    yield 'sub-driver finder' => [SubDriverFinderInterface::class];
  }

  /**
   * Tests instantiating the driver with only an alias.
   */
  public function testWithAlias(): void {
    $driver = new DrushDriver('alias');
    $this->assertEquals('alias', $driver->alias, 'The drush alias was not properly set.');
  }

  /**
   * Tests instantiating the driver with a prefixed alias.
   */
  public function testWithAliasPrefix(): void {
    $driver = new DrushDriver('@alias');
    $this->assertEquals('alias', $driver->alias, 'The drush alias did not remove the "@" prefix.');
  }

  /**
   * Tests instantiating the driver with only the root path.
   */
  public function testWithRoot(): void {
    // Bit of a hack here to use the path to this file, but all the driver cares
    // about during initialization is that the root be a directory.
    $driver = new DrushDriver('', __FILE__);
    $this->assertEquals(__FILE__, $driver->root);
  }

  /**
   * Tests instantiating the driver with missing alias and root path.
   */
  public function testWithNeither(): void {
    $this->expectException(BootstrapException::class);
    new DrushDriver('', '');
  }

  /**
   * Tests `isLegacyDrush()` correctly detects version from noisy output.
   *
   * @dataProvider dataProviderIsLegacyDrush
   */
  public function testIsLegacyDrush(string $drush_output, bool $expected): void {
    $driver = new TestDrushDriver('alias');
    $driver->drushOutput = $drush_output;
    $result = $driver->callIsLegacyDrush();
    $this->assertSame($expected, $result);
  }

  /**
   * Tests 'parseUserId()' correctly extracts UID from drush output.
   *
   * @dataProvider dataProviderParseUserId
   */
  public function testParseUserId(string $drush_output, ?int $expected): void {
    $driver = new TestDrushDriver('alias');
    $result = $driver->callParseUserId($drush_output);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testParseUserId().
   */
  public static function dataProviderParseUserId(): \Iterator {
    yield 'legacy key-value format' => [
      "User ID   :   550895\nUser name :   test\n",
      550895,
    ];
    yield 'drush 12 table format' => [
      " --------- ----------- ----------- --------------- ------------- \n  User ID   User name   User mail   User roles      User status  \n --------- ----------- ----------- --------------- ------------- \n  550895    test        test@ex.co  authenticated   1            \n --------- ----------- ----------- --------------- ------------- \n",
      550895,
    ];
    yield 'no user id present' => [
      "Some random output\n",
      NULL,
    ];
    yield 'drush 12 table uid 1' => [
      " --------- ----------- ----------- --------------- ------------- \n  User ID   User name   User mail   User roles      User status  \n --------- ----------- ----------- --------------- ------------- \n  1         admin       a@ex.co     administrator   1            \n --------- ----------- ----------- --------------- ------------- \n",
      1,
    ];
  }

  /**
   * Data provider for testIsLegacyDrush().
   */
  public static function dataProviderIsLegacyDrush(): \Iterator {
    yield 'clean modern version' => [
      "12.5.2.0\n",
      FALSE,
    ];
    yield 'deprecation warnings before version' => [
      "Deprecated: Drush\\Drush::shell(): Implicitly marking parameter \$env as nullable\nDeprecated: Consolidation\\Config\\Config::__construct(): ...\n12.5.2.0\n",
      FALSE,
    ];
    yield 'drush 9 version' => [
      "9.7.2\n",
      FALSE,
    ];
    yield 'legacy drush version' => [
      "8.4.12\n",
      TRUE,
    ];
    yield 'legacy version with noise' => [
      "Some warning output\n8.1.0\n",
      TRUE,
    ];
    yield 'three-part modern version' => [
      "13.0.0\n",
      FALSE,
    ];
  }

}

/**
 * Testable subclass that stubs the `drush()` method.
 */
class TestDrushDriver extends DrushDriver {

  /**
   * The output to return from `drush()`.
   */
  public string $drushOutput = '';

  /**
   * {@inheritdoc}
   */
  public function drush($command, array $arguments = [], array $options = []): string {
    return $this->drushOutput;
  }

  /**
   * Exposes `isLegacyDrush()` for testing.
   */
  public function callIsLegacyDrush(): bool {
    return $this->isLegacyDrush();
  }

  /**
   * Exposes `parseUserId()` for testing.
   */
  public function callParseUserId(string $info): ?int {
    return $this->parseUserId($info);
  }

}
