<?php

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
  public function testWithAlias() {
    $driver = new DrushDriver('alias');
    $this->assertEquals('alias', $driver->alias, 'The drush alias was not properly set.');
  }

  /**
   * Tests instantiating the driver with a prefixed alias.
   */
  public function testWithAliasPrefix() {
    $driver = new DrushDriver('@alias');
    $this->assertEquals('alias', $driver->alias, 'The drush alias did not remove the "@" prefix.');
  }

  /**
   * Tests instantiating the driver with only the root path.
   */
  public function testWithRoot() {
    // Bit of a hack here to use the path to this file, but all the driver cares
    // about during initialization is that the root be a directory.
    $driver = new DrushDriver('', __FILE__);
    $this->assertEquals(__FILE__, $driver->root);
  }

  /**
   * Tests instantiating the driver with missing alias and root path.
   */
  public function testWithNeither() {
    $this->expectException(BootstrapException::class);
    new DrushDriver('', '');
  }

  /**
   * Tests `isLegacyDrush()` correctly detects version from noisy output.
   *
   * @dataProvider dataProviderIsLegacyDrush
   */
  public function testIsLegacyDrush($drush_output, $expected) {
    $driver = new TestDrushDriver('alias');
    $driver->drushOutput = $drush_output;
    $result = $driver->callIsLegacyDrush();
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testIsLegacyDrush().
   */
  public function dataProviderIsLegacyDrush() {
    return [
      'clean modern version' => [
        "12.5.2.0\n",
        FALSE,
      ],
      'deprecation warnings before version' => [
        "Deprecated: Drush\\Drush::shell(): Implicitly marking parameter \$env as nullable\nDeprecated: Consolidation\\Config\\Config::__construct(): ...\n12.5.2.0\n",
        FALSE,
      ],
      'drush 9 version' => [
        "9.7.2\n",
        FALSE,
      ],
      'legacy drush version' => [
        "8.4.12\n",
        TRUE,
      ],
      'legacy version with noise' => [
        "Some warning output\n8.1.0\n",
        TRUE,
      ],
      'three-part modern version' => [
        "13.0.0\n",
        FALSE,
      ],
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
  public function drush($command, array $arguments = [], array $options = []) {
    return $this->drushOutput;
  }

  /**
   * Exposes `isLegacyDrush()` for testing.
   */
  public function callIsLegacyDrush() {
    return $this->isLegacyDrush();
  }

}
