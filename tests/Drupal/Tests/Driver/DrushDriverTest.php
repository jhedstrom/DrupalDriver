<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver;

use Drupal\Driver\DrushDriver;
use Drupal\Driver\Exception\BootstrapException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Drush driver.
 */
class DrushDriverTest extends TestCase {

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
   *
   * @var string
   */
  public $drushOutput = '';

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
  public function callParseUserId($info): ?int {
    return $this->parseUserId($info);
  }

}
