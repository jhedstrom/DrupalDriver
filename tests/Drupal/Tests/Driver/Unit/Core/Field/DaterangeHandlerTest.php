<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Composer\InstalledVersions;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Driver\Core\Field\DaterangeHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DaterangeHandler field handler.
 *
 * Only empty/null ranges are exercised - full date parsing exercises
 * DrupalDateTime and requires the full Drupal container.
 */
class DaterangeHandlerTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (!$this->loadDatetimeModuleInterface()) {
      $this->markTestSkipped('drupal/core datetime module classes are not available.');
    }

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('timezone.default')->willReturn('UTC');

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('system.date')->willReturn($config);

    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * Tests that empty start/end values produce NULL entries.
   */
  public function testExpandHandlesEmptyValuesAsNull(): void {
    $reflection = new \ReflectionClass(DaterangeHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $result = $handler->expand([
      ['value' => NULL, 'end_value' => NULL],
      [NULL, NULL],
    ]);

    $this->assertSame([
      ['value' => NULL, 'end_value' => NULL],
      ['value' => NULL, 'end_value' => NULL],
    ], $result);
  }

  /**
   * Loads the datetime module interface from the Composer-resolved core path.
   */
  protected function loadDatetimeModuleInterface(): bool {
    if (interface_exists(DateTimeItemInterface::class)) {
      return TRUE;
    }

    if (!class_exists(InstalledVersions::class)) {
      return FALSE;
    }

    $core_path = InstalledVersions::getInstallPath('drupal/core');
    if ($core_path === NULL) {
      return FALSE;
    }

    $interface_file = $core_path . '/modules/datetime/src/Plugin/Field/FieldType/DateTimeItemInterface.php';
    if (!is_file($interface_file)) {
      return FALSE;
    }

    require_once $interface_file;

    return interface_exists(DateTimeItemInterface::class);
  }

}
