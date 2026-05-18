<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use PHPUnit\Framework\Attributes\DataProvider;
use Composer\InstalledVersions;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\DatetimeHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the DatetimeHandler field handler.
 *
 * Full date-parsing behaviour exercises DrupalDateTime, which in turn requires
 * the language_manager service and a full Drupal container, so only the
 * early-return paths (empty values) are asserted here.
 *
 * @group fields
 */
#[Group('fields')]
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
   * Tests that empty values are accepted in every input shape.
   *
   * @param mixed $input
   *   Any of the loose shapes 'normalise()' accepts.
   * @param array<int, array<string, mixed>> $expected
   *   The expected expand() output: a list of records keyed by 'value'.
   *
   * @dataProvider dataProviderExpandPreservesEmptyValuesAsNull
   */
  #[DataProvider('dataProviderExpandPreservesEmptyValuesAsNull')]
  public function testExpandPreservesEmptyValuesAsNull(mixed $input, array $expected): void {
    $handler = $this->createHandler('datetime');

    $result = $handler->expand($input);

    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpandPreservesEmptyValuesAsNull().
   *
   * Non-empty dates exercise DrupalDateTime, which needs the language_manager
   * service and a full container; those cases live in the kernel test. The
   * shape coverage here uses empty values so that 'formatDateValue()' early
   * returns and the only assertion is on the normalised record shape.
   */
  public static function dataProviderExpandPreservesEmptyValuesAsNull(): \Iterator {
    yield 'bare empty string scalar' => [
      '',
      [['value' => NULL]],
    ];
    yield 'bare NULL scalar' => [
      NULL,
      [['value' => NULL]],
    ];
    yield 'list of empty scalars' => [
      ['', NULL],
      [['value' => NULL], ['value' => NULL]],
    ];
    yield 'single record with empty value' => [
      ['value' => ''],
      [['value' => NULL]],
    ];
    yield 'list of records with empty values' => [
      [['value' => ''], ['value' => NULL]],
      [['value' => NULL], ['value' => NULL]],
    ];
  }

  /**
   * Creates a DatetimeHandler with fieldInfo and main property injected.
   *
   * The fieldInfo mock is still needed for getSetting('datetime_type')
   * (used by formatDateValue); mainProperty is injected separately because
   * normalise() reads it as a property, not via fieldInfo.
   */
  protected function createHandler(string $datetime_type): DatetimeHandler {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getSetting')
      ->with('datetime_type')
      ->willReturn($datetime_type);

    $reflection = new \ReflectionClass(DatetimeHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $info_property = new \ReflectionProperty(DatetimeHandler::class, 'fieldInfo');
    $info_property->setValue($handler, $field_info);

    $main_property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_property->setValue($handler, 'value');

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
