<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Component\Utility\Random;
use Drupal\Driver\BlackboxDriver;
use Drupal\Driver\BlackboxDriverInterface;
use Drupal\Driver\Capability\AuthenticationCapabilityInterface;
use Drupal\Driver\Capability\CacheCapabilityInterface;
use Drupal\Driver\Capability\ConfigCapabilityInterface;
use Drupal\Driver\Capability\ContentCapabilityInterface;
use Drupal\Driver\Capability\CronCapabilityInterface;
use Drupal\Driver\Capability\FieldCapabilityInterface;
use Drupal\Driver\Capability\LanguageCapabilityInterface;
use Drupal\Driver\Capability\MailCapabilityInterface;
use Drupal\Driver\Capability\ModuleCapabilityInterface;
use Drupal\Driver\Capability\RoleCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Capability\WatchdogCapabilityInterface;
use Drupal\Driver\DriverInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests BlackboxDriver's interface and capability surface.
 *
 * @group drivers
 * @group blackbox
 */
#[Group('drivers')]
#[Group('blackbox')]
class BlackboxDriverTest extends TestCase {

  /**
   * Tests that BlackboxDriver satisfies its declared interfaces.
   */
  public function testImplementsExpectedInterfaces(): void {
    $driver = new BlackboxDriver();
    $this->assertInstanceOf(BlackboxDriverInterface::class, $driver);
    $this->assertInstanceOf(DriverInterface::class, $driver);
  }

  /**
   * Tests that BlackboxDriver considers itself bootstrapped.
   */
  public function testIsBootstrappedReturnsTrue(): void {
    $driver = new BlackboxDriver();
    $this->assertTrue($driver->isBootstrapped());
  }

  /**
   * Tests that bootstrap() is a no-op.
   */
  public function testBootstrapIsNoop(): void {
    $driver = new BlackboxDriver();
    $driver->bootstrap();
    $this->addToAssertionCount(1);
  }

  /**
   * Tests that getRandom() returns a usable generator.
   */
  public function testGetRandomReturnsInstance(): void {
    $driver = new BlackboxDriver();
    $this->assertInstanceOf(Random::class, $driver->getRandom());
  }

  /**
   * Tests that an injected random generator is returned as-is.
   */
  public function testGetRandomReturnsInjectedInstance(): void {
    $random = new Random();
    $driver = new BlackboxDriver($random);
    $this->assertSame($random, $driver->getRandom());
  }

  /**
 * Tests that BlackboxDriver does not claim unsupported capabilities.
 *
 * @param string $capability_class
 *   The fully qualified capability interface name.
 *
 * @dataProvider dataProviderDoesNotImplementCapability
 */
  #[DataProvider('dataProviderDoesNotImplementCapability')]
  public function testDoesNotImplementCapability(string $capability_class): void {
    $driver = new BlackboxDriver();
    $this->assertNotInstanceOf($capability_class, $driver, sprintf(
      'BlackboxDriver must not claim to implement %s.',
      $capability_class
    ));
  }

  /**
   * Data provider listing every capability BlackboxDriver must not declare.
   */
  public static function dataProviderDoesNotImplementCapability(): \Iterator {
    yield 'authentication' => [AuthenticationCapabilityInterface::class];
    yield 'cache' => [CacheCapabilityInterface::class];
    yield 'config' => [ConfigCapabilityInterface::class];
    yield 'content' => [ContentCapabilityInterface::class];
    yield 'cron' => [CronCapabilityInterface::class];
    yield 'field' => [FieldCapabilityInterface::class];
    yield 'language' => [LanguageCapabilityInterface::class];
    yield 'mail' => [MailCapabilityInterface::class];
    yield 'module' => [ModuleCapabilityInterface::class];
    yield 'role' => [RoleCapabilityInterface::class];
    yield 'user' => [UserCapabilityInterface::class];
    yield 'watchdog' => [WatchdogCapabilityInterface::class];
  }

}
