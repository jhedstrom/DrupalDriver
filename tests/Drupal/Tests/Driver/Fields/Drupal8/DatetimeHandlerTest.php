<?php

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Fields\Drupal8\DatetimeHandler;
use PHPUnit\Framework\TestCase;

// The datetime module constants used by DatetimeHandler live outside the
// default Drupal core autoload, so they must be loaded explicitly here.
require_once __DIR__ . '/../../../../../../drupal/core/modules/datetime/src/Plugin/Field/FieldType/DateTimeItemInterface.php';
require_once __DIR__ . '/../../../../../../drupal/core/modules/datetime/src/Plugin/Field/FieldType/DateTimeItem.php';

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
  public function testExpandPreservesEmptyValuesAsNull() {
    $handler = $this->createHandler('datetime');

    $result = $handler->expand(['', NULL]);

    $this->assertSame([NULL, NULL], $result);
  }

  /**
   * Creates a DatetimeHandler with a fieldInfo mock returning datetime_type.
   */
  protected function createHandler($datetime_type) {
    $field_info = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getSetting'])
      ->getMock();
    $field_info->method('getSetting')
      ->with('datetime_type')
      ->willReturn($datetime_type);

    $reflection = new \ReflectionClass(DatetimeHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(DatetimeHandler::class, 'fieldInfo');
    $property->setAccessible(TRUE);
    $property->setValue($handler, $field_info);

    return $handler;
  }

}
