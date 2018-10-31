<?php

namespace Drupal\Tests\Driver\Kernel;

use Drupal\Driver\DrupalDriver;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides common functionality for the Driver kernel tests.
 */
trait DriverKernelTestTrait {
  /**
   * Drupal Driver.
   *
   * @var \Drupal\Driver\DriverInterface
   */
  protected $driver;

  /**
   * Additional setup needed for both entity and field kernel tests.
   */
  protected function setUpDriver() {
    // @todo These hard-coded values are only necessary to test the driver's
    // methods directly. Doing so becomes less important once more logic has
    // been moved off the driver into other directly testable classes.
    $this->driver = new DrupalDriver(KernelTestBase::getDrupalRoot(), 'http://nothing');
    $this->driver->setCoreFromVersion();
  }

}
