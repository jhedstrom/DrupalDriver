<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver;

use Drupal\Driver\Capability\AuthenticationCapabilityInterface;
use Drupal\Driver\Capability\CacheCapabilityInterface;
use Drupal\Driver\Capability\ConfigCapabilityInterface;
use Drupal\Driver\Capability\ContentCapabilityInterface;
use Drupal\Driver\Capability\FieldCapabilityInterface;
use Drupal\Driver\Capability\LanguageCapabilityInterface;
use Drupal\Driver\Capability\MailCapabilityInterface;
use Drupal\Driver\Capability\ModuleCapabilityInterface;
use Drupal\Driver\Capability\RoleCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Capability\WatchdogCapabilityInterface;
use Drupal\Driver\DriverInterface;
use Drupal\Driver\DrupalDriver;
use Drupal\Driver\DrupalDriverInterface;
use Drupal\Driver\SubDriverFinderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests that DrupalDriver declares the full capability surface.
 *
 * Class-level conformance only; runtime behaviour requires a real Drupal
 * bootstrap and is exercised by the Kernel test suite.
 */
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
   * @dataProvider dataProviderAllCapabilities
   */
  public function testImplementsCapability(string $capability_class): void {
    $this->assertTrue(is_subclass_of(DrupalDriver::class, $capability_class), sprintf(
      'DrupalDriver must implement %s.',
      $capability_class
    ));
  }

  /**
   * Data provider listing every capability the Drupal driver must support.
   */
  public static function dataProviderAllCapabilities(): \Iterator {
    yield 'authentication' => [AuthenticationCapabilityInterface::class];
    yield 'cache' => [CacheCapabilityInterface::class];
    yield 'config' => [ConfigCapabilityInterface::class];
    yield 'content' => [ContentCapabilityInterface::class];
    yield 'field' => [FieldCapabilityInterface::class];
    yield 'language' => [LanguageCapabilityInterface::class];
    yield 'mail' => [MailCapabilityInterface::class];
    yield 'module' => [ModuleCapabilityInterface::class];
    yield 'role' => [RoleCapabilityInterface::class];
    yield 'user' => [UserCapabilityInterface::class];
    yield 'watchdog' => [WatchdogCapabilityInterface::class];
  }

}
