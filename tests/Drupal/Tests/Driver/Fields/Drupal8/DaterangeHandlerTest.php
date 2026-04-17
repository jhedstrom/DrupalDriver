<?php

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Fields\Drupal8\DaterangeHandler;
use PHPUnit\Framework\TestCase;

// The datetime module constants used downstream are not part of the default
// Drupal core autoload, so they must be loaded explicitly here.
require_once __DIR__ . '/../../../../../../drupal/core/modules/datetime/src/Plugin/Field/FieldType/DateTimeItemInterface.php';

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
  public function testExpandHandlesEmptyValuesAsNull() {
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

}
