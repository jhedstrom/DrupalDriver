<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

use Drupal\Driver\Core\Core;
use Drupal\Driver\DrupalDriver;
use Drupal\Driver\Exception\BootstrapException;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for 'DrupalDriver' construction and version detection.
 *
 * Constructs a real 'DrupalDriver' against the Drupal root that the kernel
 * test framework already has on disk. That path contains an 'autoload.php'
 * and a 'core/includes/bootstrap.inc', so the internal version detection
 * runs end-to-end instead of being bypassed via reflection.
 *
 * @group drivers
 * @group drupal
 */
#[Group('drivers')]
#[Group('drupal')]
class DrupalDriverConstructionKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = ['system'];

  /**
   * Tests that the constructor resolves the root and detects the version.
   */
  public function testConstructorDetectsVersion(): void {
    $driver = new DrupalDriver($this->root, 'default');

    $version = $driver->getDrupalVersion();

    $this->assertGreaterThanOrEqual(10, $version, 'Driver should detect Drupal 10 or higher.');
  }

  /**
   * Tests that an invalid Drupal root raises 'BootstrapException'.
   */
  public function testConstructorRejectsMissingRoot(): void {
    $this->expectException(BootstrapException::class);
    $this->expectExceptionMessageMatches('/No Drupal installation found/');

    new DrupalDriver('/nonexistent/path/that/will/never/exist', 'default');
  }

  /**
   * Tests 'setCoreFromVersion()' picks the default Core.
   */
  public function testSetCoreFromVersionSelectsDefaultCore(): void {
    $driver = new DrupalDriver($this->root, 'default');
    $driver->setCoreFromVersion();

    $this->assertInstanceOf(Core::class, $driver->getCore());
  }

}
