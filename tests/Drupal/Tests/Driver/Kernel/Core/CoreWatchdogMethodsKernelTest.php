<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for Core::watchdogFetch().
 *
 * @group core
 */
#[Group('core')]
class CoreWatchdogMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = ['system', 'dblog'];

  /**
   * The Core driver under test.
   */
  protected Core $core;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('dblog', ['watchdog']);
    $this->core = new Core($this->root);
  }

  /**
   * Tests that 'watchdogFetch()' returns entries written to dblog.
   */
  public function testWatchdogFetchReturnsRecentEntries(): void {
    \Drupal::logger('php')->warning('Something went wrong.');
    \Drupal::logger('cron')->info('Cron ran.');

    $output = $this->core->watchdogFetch();

    $this->assertStringContainsString('Something went wrong.', $output);
    $this->assertStringContainsString('Cron ran.', $output);
  }

  /**
   * Tests filtering by channel type.
   */
  public function testWatchdogFetchFiltersByType(): void {
    \Drupal::logger('php')->warning('Php problem.');
    \Drupal::logger('cron')->info('Cron fine.');

    $output = $this->core->watchdogFetch(count: 10, type: 'php');

    $this->assertStringContainsString('Php problem.', $output);
    $this->assertStringNotContainsString('Cron fine.', $output);
  }

  /**
   * Tests filtering by symbolic severity name.
   *
   * @param string $severity
   *   Severity name passed to 'watchdogFetch()'.
   * @param int $expected_level
   *   The log level that should appear in the output line.
   *
   * @dataProvider dataProviderWatchdogFetchFiltersBySymbolicSeverity
   */
  #[DataProvider('dataProviderWatchdogFetchFiltersBySymbolicSeverity')]
  public function testWatchdogFetchFiltersBySymbolicSeverity(string $severity, int $expected_level): void {
    \Drupal::logger('php')->log($expected_level, 'Matched severity message.');
    \Drupal::logger('php')->info('Ignored info message.');

    $output = $this->core->watchdogFetch(count: 10, severity: $severity);

    $this->assertStringContainsString('Matched severity message.', $output);
    $this->assertStringNotContainsString('Ignored info message.', $output);
  }

  /**
   * Data provider for 'testWatchdogFetchFiltersBySymbolicSeverity()'.
   */
  public static function dataProviderWatchdogFetchFiltersBySymbolicSeverity(): \Iterator {
    yield 'warning' => ['warning', RfcLogLevel::WARNING];
    yield 'error' => ['error', RfcLogLevel::ERROR];
    yield 'notice' => ['notice', RfcLogLevel::NOTICE];
  }

  /**
   * Tests that 'watchdogFetch()' throws when dblog is not installed.
   */
  public function testWatchdogFetchThrowsWithoutDblog(): void {
    \Drupal::service('module_installer')->uninstall(['dblog']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/dblog module/');

    $this->core->watchdogFetch();
  }

}
