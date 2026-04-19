<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Component\Utility\Random;
use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for cache-related methods on Core via the driver.
 *
 * @group core
 */
#[Group('core')]
class CoreCacheMethodsKernelTest extends KernelTestBase {

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
   * Tests that 'cacheClear()' dispatches without error.
   */
  public function testCacheClearDispatches(): void {
    // Populate a cache entry so the clear has something to flush.
    \Drupal::cache()->set('drupal_driver_test:sentinel', 'value');
    $this->assertNotFalse(\Drupal::cache()->get('drupal_driver_test:sentinel'));

    $this->core->cacheClear();

    $this->assertFalse(\Drupal::cache()->get('drupal_driver_test:sentinel'));
  }

  /**
   * Tests that 'cacheClearStatic()' resets Drupal's static caches.
   */
  public function testCacheClearStaticResetsStatics(): void {
    $counter = &drupal_static('drupal_driver_test_counter');
    $counter = 7;
    $this->assertSame(7, drupal_static('drupal_driver_test_counter'));

    $this->core->cacheClearStatic();

    $this->assertNull(drupal_static('drupal_driver_test_counter'));
  }

  /**
   * Tests that 'getExtensionPathList()' includes the enabled system module.
   */
  public function testGetExtensionPathListIncludesEnabledModules(): void {
    $paths = $this->core->getExtensionPathList();

    $this->assertContains(
      $this->root . DIRECTORY_SEPARATOR . 'core/modules/system',
      $paths,
      'Enabled system module path should appear in the extension list.'
    );
  }

  /**
   * Tests that 'getRandom()' returns the random generator.
   */
  public function testGetRandomReturnsInjectedGenerator(): void {
    $random = new Random();
    $core = new Core($this->root, 'default', $random);

    $this->assertSame($random, $core->getRandom());
  }

}
