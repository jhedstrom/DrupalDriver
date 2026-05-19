<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Composer\InstalledVersions;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Driver\Core\Field\DaterangeHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the DaterangeHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class DaterangeHandlerTest extends FieldHandlerUnitTestBase {

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
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getSetting')
      ->with('datetime_type')
      ->willReturn('datetime');

    $reflection = new \ReflectionClass(DaterangeHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $info_property = new \ReflectionProperty(DaterangeHandler::class, 'fieldInfo');
    $info_property->setValue($handler, $field_info);

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'empty array returns empty list' => [
      [],
      [],
      NULL,
      NULL,
    ];
    yield 'NULL endpoints stay NULL' => [
      [['value' => NULL, 'end_value' => NULL]],
      [['value' => NULL, 'end_value' => NULL]],
      NULL,
      NULL,
    ];
    yield 'positional NULL pair stays NULL' => [
      [[NULL, NULL]],
      [['value' => NULL, 'end_value' => NULL]],
      NULL,
      NULL,
    ];
    yield 'single keyed record auto-wrapped' => [
      ['value' => NULL, 'end_value' => NULL],
      [['value' => NULL, 'end_value' => NULL]],
      NULL,
      NULL,
    ];
    yield 'multi-delta NULL endpoints' => [
      [
        ['value' => NULL, 'end_value' => NULL],
        ['value' => NULL, 'end_value' => NULL],
      ],
      [
        ['value' => NULL, 'end_value' => NULL],
        ['value' => NULL, 'end_value' => NULL],
      ],
      NULL,
      NULL,
    ];

    yield 'non-array element in list rejected' => [
      [
        ['value' => '2026-07-15T09:00:00', 'end_value' => '2026-07-15T17:00:00'],
        'not-a-record',
      ],
      NULL,
      \InvalidArgumentException::class,
      'Daterange field record must be an array',
    ];
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
