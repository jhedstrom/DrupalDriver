<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\Driver\Kernel\DriverKernelTestTrait;

/**
 * Base class for all Driver entity kernel tests.
 */
class DriverEntityKernelTestBase extends EntityKernelTestBase {

  use DriverKernelTestTrait;

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
  protected $entityType;

  /**
   * Entity storage.
   *
   * * @var \Drupal\Core\Entity\EntityStorageInterface;
   */
  protected $storage;

  protected function setUp() {
    parent::setUp();
    $this->setUpDriver();
    $this->storage = \Drupal::entityTypeManager()->getStorage($this->entityType);
  }

}
