<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel test for config-related methods on Core via the driver.
 */
class CoreConfigMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = ['system'];

  /**
   * The Core driver under test.
   */
  protected Core $core;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
    $this->core = new Core($this->root);
  }

  /**
   * Tests configSet writes and configGet reads the same value back.
   */
  public function testConfigSetAndGetRoundTrip(): void {
    $this->core->configSet('system.site', 'name', 'DrupalDriver Test Site');

    $this->assertSame('DrupalDriver Test Site', $this->core->configGet('system.site', 'name'));
  }

  /**
   * Tests configGetOriginal returns the value as stored (no apply-yet).
   *
   * ConfigBase::getOriginal tracks the not-yet-applied-to-config baseline, so
   * it matches configGet after a save. This test just asserts the passthrough
   * works and does not blow up on an empty key.
   */
  public function testConfigGetOriginalExposesStoredValue(): void {
    $this->core->configSet('system.site', 'name', 'Pinned');

    $this->assertSame('Pinned', $this->core->configGetOriginal('system.site', 'name'));
  }

}
