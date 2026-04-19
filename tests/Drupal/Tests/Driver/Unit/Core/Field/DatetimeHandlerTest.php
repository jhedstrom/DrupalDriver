<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Composer\InstalledVersions;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Driver\Core\Field\DatetimeHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DatetimeHandler field handler.
 *
 * Full date-parsing behaviour exercises DrupalDateTime, which in turn requires
 * the language_manager service and a full Drupal container, so only the
 * early-return paths (empty values) are asserted here.
 */
class DatetimeHandlerTest extends TestCase {

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
   * Tests that empty strings and NULLs pass through as NULL values.
   */
  public function testExpandPreservesEmptyValuesAsNull(): void {
    $handler = $this->createHandler('datetime');

    $result = $handler->expand(['', NULL]);

    $this->assertSame([NULL, NULL], $result);
  }

  /**
   * Creates a DatetimeHandler with a fieldInfo mock returning datetime_type.
   */
  protected function createHandler(string $datetime_type): DatetimeHandler {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getSetting')
      ->with('datetime_type')
      ->willReturn($datetime_type);

    $reflection = new \ReflectionClass(DatetimeHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(DatetimeHandler::class, 'fieldInfo');
    $property->setValue($handler, $field_info);

    return $handler;
  }

  /**
   * Loads datetime module classes from the Composer-resolved drupal/core path.
   *
   * The datetime module lives outside the default drupal/core PSR-4 namespace
   * coverage, so the relevant files are loaded explicitly. Returns TRUE when
   * DateTimeItemInterface is available after loading.
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
    $item_file = $core_path . '/modules/datetime/src/Plugin/Field/FieldType/DateTimeItem.php';

    if (!is_file($interface_file) || !is_file($item_file)) {
      return FALSE;
    }

    require_once $interface_file;
    require_once $item_file;

    return interface_exists(DateTimeItemInterface::class);
  }

}
