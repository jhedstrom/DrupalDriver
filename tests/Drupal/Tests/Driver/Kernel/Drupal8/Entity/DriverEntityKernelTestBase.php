<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\Driver\Kernel\DriverKernelTestTrait;
use Drupal\Driver\Plugin\DriverFieldPluginManager;
use Drupal\Driver\Plugin\DriverEntityPluginManager;

/**
 * Base class for all Driver entity kernel tests.
 */
class DriverEntityKernelTestBase extends EntityKernelTestBase
{

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

  /**
   * Absolute path to test project plugins.
   *
   * * @var string;
   */
    protected $projectPluginRoot;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpDriver();
        if (empty($this->config)) {
            $this->storage = \Drupal::entityTypeManager()
            ->getStorage($this->entityType);
        }

        $namespaces = \Drupal::service('container.namespaces');
        $cache_backend = \Drupal::service('cache.discovery');
        $module_handler = \Drupal::service('module_handler');

        $reflection = new \ReflectionClass($this);
        $this->projectPluginRoot = dirname($reflection->getFileName(), 7) . "/test_project";
        $this->fieldPluginManager = new DriverFieldPluginManager($namespaces, $cache_backend, $module_handler, 8, $this->projectPluginRoot);
        $this->entityPluginManager = new DriverEntityPluginManager($namespaces, $cache_backend, $module_handler, 8, $this->projectPluginRoot);
    }
}
