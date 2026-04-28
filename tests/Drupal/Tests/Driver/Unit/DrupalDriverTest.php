<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Driver\Capability\AuthenticationCapabilityInterface;
use Drupal\Driver\Capability\CacheCapabilityInterface;
use Drupal\Driver\Capability\ConfigCapabilityInterface;
use Drupal\Driver\Capability\ContentCapabilityInterface;
use Drupal\Driver\Capability\CronCapabilityInterface;
use Drupal\Driver\Capability\LanguageCapabilityInterface;
use Drupal\Driver\Capability\MailCapabilityInterface;
use Drupal\Driver\Capability\ModuleCapabilityInterface;
use Drupal\Driver\Capability\RoleCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Capability\WatchdogCapabilityInterface;
use Drupal\Driver\DriverInterface;
use Drupal\Driver\DrupalDriver;
use Drupal\Driver\DrupalDriverInterface;
use Drupal\Driver\Exception\BootstrapException;
use Drupal\Driver\SubDriverFinderInterface;
use Drupal\Tests\Driver\Unit\Fixtures\FakeVersionDrupalDriver;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests that DrupalDriver declares the full capability surface.
 *
 * Class-level conformance only; runtime behaviour requires a real Drupal
 * bootstrap and is exercised by the Kernel test suite.
 *
 * @group drivers
 * @group drupal
 */
#[Group('drivers')]
#[Group('drupal')]
class DrupalDriverTest extends TestCase {

  /**
   * Tests that DrupalDriver implements its composite contract.
   */
  public function testImplementsDrupalDriverInterface(): void {
    $this->assertTrue(is_subclass_of(DrupalDriver::class, DrupalDriverInterface::class));
    $this->assertTrue(is_subclass_of(DrupalDriver::class, DriverInterface::class));
    $this->assertTrue(is_subclass_of(DrupalDriver::class, SubDriverFinderInterface::class));
  }

  /**
 * Tests that DrupalDriver advertises every capability.
 *
 * @param string $capability_class
 *   The capability interface name.
 *
 * @dataProvider dataProviderImplementsCapability
 */
  #[DataProvider('dataProviderImplementsCapability')]
  public function testImplementsCapability(string $capability_class): void {
    $this->assertTrue(is_subclass_of(DrupalDriver::class, $capability_class), sprintf(
      'DrupalDriver must implement %s.',
      $capability_class
    ));
  }

  /**
   * Data provider listing every capability the Drupal driver must support.
   */
  public static function dataProviderImplementsCapability(): \Iterator {
    yield 'authentication' => [AuthenticationCapabilityInterface::class];
    yield 'cache' => [CacheCapabilityInterface::class];
    yield 'config' => [ConfigCapabilityInterface::class];
    yield 'content' => [ContentCapabilityInterface::class];
    yield 'cron' => [CronCapabilityInterface::class];
    yield 'language' => [LanguageCapabilityInterface::class];
    yield 'mail' => [MailCapabilityInterface::class];
    yield 'module' => [ModuleCapabilityInterface::class];
    yield 'role' => [RoleCapabilityInterface::class];
    yield 'user' => [UserCapabilityInterface::class];
    yield 'watchdog' => [WatchdogCapabilityInterface::class];
  }

  /**
   * Tests that 'detectMajorVersion()' rejects an unparseable version string.
   *
   * Uses a fixture subclass to inject a non-numeric version value without
   * touching the real '\Drupal::VERSION' constant.
   */
  public function testDetectMajorVersionRejectsNonNumeric(): void {
    $this->expectException(BootstrapException::class);
    $this->expectExceptionMessageMatches('/Unable to extract major Drupal core version/');

    FakeVersionDrupalDriver::$nextVersion = 'zz.x';
    new FakeVersionDrupalDriver(__DIR__, 'default');
  }

  /**
   * Tests that 'detectMajorVersion()' rejects pre-10 versions.
   */
  public function testDetectMajorVersionRejectsPre10(): void {
    $this->expectException(BootstrapException::class);
    $this->expectExceptionMessageMatches('/Unsupported Drupal core version/');

    FakeVersionDrupalDriver::$nextVersion = '9.5.0';
    new FakeVersionDrupalDriver(__DIR__, 'default');
  }

}
