<?php

namespace Drupal\Tests\Driver\Kernel;

use Drupal\Driver\DrupalDriver;

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

  protected function setUpDriver() {
    $this->driver = new DrupalDriver('/app/web', 'http://nothing');
    $this->driver->setCoreFromVersion();
  }

}