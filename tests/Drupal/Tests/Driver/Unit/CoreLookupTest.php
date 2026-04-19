<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Driver\Core\Core;
use Drupal\Driver\Core99\Core as Core99Core;
use Drupal\Driver\DrupalDriver;
use PHPUnit\Framework\TestCase;

/**
 * Tests DrupalDriver::setCoreFromVersion() lookup chain.
 */
class CoreLookupTest extends TestCase {

  /**
   * Verifies that fixture classes are autoloaded via autoload-dev.
   */
  public function testCore99ClassIsAutoloadable(): void {
    $this->assertTrue(
      class_exists(Core99Core::class),
      'Drupal\\Driver\\Core99\\Core must be autoloadable via tests/fixtures/'
    );
  }

  /**
   * Tests that a version-specific Core class is preferred over the default.
   */
  public function testLookupPicksVersionOverride(): void {
    $driver = $this->createDriverWithVersion(99);
    $driver->setCoreFromVersion();

    $this->assertInstanceOf(Core99Core::class, $driver->getCore());
    $this->assertSame('Core99\\Core', $driver->getCore()::MARKER);
  }

  /**
   * Tests fallback to Core\Core when no version-specific class exists.
   */
  public function testLookupFallsBackToDefault(): void {
    // Version 50: no Core50 fixture exists, so the chain falls through.
    $driver = $this->createDriverWithVersion(50);
    $driver->setCoreFromVersion();

    $this->assertInstanceOf(Core::class, $driver->getCore());
  }

  /**
   * Creates a DrupalDriver instance with a fixed version, bypassing bootstrap.
   *
   * Uses ReflectionClass to skip the constructor (which requires a real Drupal
   * root) and injects the required private properties directly.
   *
   * @param int $version
   *   The Drupal major version to report.
   *
   * @return \Drupal\Driver\DrupalDriver
   *   The prepared driver instance.
   */
  protected function createDriverWithVersion(int $version): DrupalDriver {
    $reflection = new \ReflectionClass(DrupalDriver::class);
    /** @var \Drupal\Driver\DrupalDriver $driver */
    $driver = $reflection->newInstanceWithoutConstructor();

    $root_prop = $reflection->getProperty('drupalRoot');
    $root_prop->setValue($driver, __DIR__);

    $uri_prop = $reflection->getProperty('uri');
    $uri_prop->setValue($driver, 'default');

    $driver->version = $version;

    return $driver;
  }

}
