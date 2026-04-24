<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Component\Utility\Random;
use Drupal\Driver\Core\CoreInterface;
use Drupal\Driver\DrupalDriver;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Exercises every 'DrupalDriver' public method to guarantee line coverage.
 *
 * 'DrupalDriver' is a thin facade over 'CoreInterface'; the tests here verify
 * that each method delegates to the corresponding core method. Kernel tests
 * under 'Kernel/Core/' exercise the behaviour end-to-end.
 *
 * @group drivers
 * @group drupal
 */
#[Group('drivers')]
#[Group('drupal')]
class DrupalDriverDelegationTest extends TestCase {

  /**
   * Tests that 'getCore()' returns the injected core instance.
   */
  public function testGetCoreReturnsInjectedCore(): void {
    $core = $this->createMock(CoreInterface::class);
    $driver = $this->createDriverWithCore($core);

    $this->assertSame($core, $driver->getCore());
  }

  /**
   * Tests that 'getRandom()' delegates to the core.
   */
  public function testGetRandomDelegatesToCore(): void {
    $random = new Random();
    $core = $this->createMock(CoreInterface::class);
    $core->expects($this->once())->method('getRandom')->willReturn($random);

    $driver = $this->createDriverWithCore($core);

    $this->assertSame($random, $driver->getRandom());
  }

  /**
   * Tests that 'bootstrap()' calls the core and flips the bootstrapped flag.
   */
  public function testBootstrapDelegatesToCore(): void {
    $core = $this->createMock(CoreInterface::class);
    $core->expects($this->once())->method('bootstrap');

    $driver = $this->createDriverWithCore($core);
    $this->assertFalse($driver->isBootstrapped());

    $driver->bootstrap();

    $this->assertTrue($driver->isBootstrapped());
  }

  /**
   * Tests that 'getSubDriverPaths()' bootstraps then returns extension paths.
   */
  public function testGetSubDriverPathsDelegatesToCore(): void {
    $core = $this->createMock(CoreInterface::class);
    $core->expects($this->once())->method('bootstrap');
    $core->expects($this->once())
      ->method('getExtensionPathList')
      ->willReturn(['/module-a', '/module-b']);

    $driver = $this->createDriverWithCore($core);

    $this->assertSame(['/module-a', '/module-b'], $driver->getSubDriverPaths());
  }

  /**
   * Tests that 'setCore()' assigns the injected instance verbatim.
   *
   * The class name and namespace of the injected core are not inspected -
   * any implementation of 'CoreInterface' is accepted.
   */
  public function testSetCoreAssignsInjectedInstance(): void {
    $driver = $this->createDriverWithCore($this->createMock(CoreInterface::class));
    $custom = $this->createMock(CoreInterface::class);

    $driver->setCore($custom);

    $this->assertSame($custom, $driver->getCore());
  }

  /**
   * Tests that 'login()' is a no-op when the core does not support auth.
   */
  public function testLoginNoopWithNonAuthCore(): void {
    $core = $this->createMock(CoreInterface::class);
    $driver = $this->createDriverWithCore($core);

    $driver->login(new \stdClass());

    $this->addToAssertionCount(1);
  }

  /**
   * Tests that 'logout()' is a no-op when the core does not support auth.
   */
  public function testLogoutNoopWithNonAuthCore(): void {
    $core = $this->createMock(CoreInterface::class);
    $driver = $this->createDriverWithCore($core);

    $driver->logout();

    $this->addToAssertionCount(1);
  }

  /**
 * Tests that every delegating method forwards to the matching core method.
 *
 * @param string $driver_method
 *   The 'DrupalDriver' method to invoke.
 * @param array<int, mixed> $args
 *   Positional arguments.
 * @param string $core_method
 *   The expected core method to be invoked with the same args.
 *
 * @dataProvider dataProviderForwardsToCore
 */
  #[DataProvider('dataProviderForwardsToCore')]
  public function testForwardsToCore(string $driver_method, array $args, string $core_method): void {
    $core = $this->createMock(CoreInterface::class);
    $core->expects($this->once())->method($core_method)->with(...$args);

    $driver = $this->createDriverWithCore($core);
    $driver->{$driver_method}(...$args);
  }

  /**
   * Data provider listing every delegating method and its arguments.
   */
  public static function dataProviderForwardsToCore(): \Iterator {
    $user = new \stdClass();
    $node = new \stdClass();
    $term = new \stdClass();
    $entity = new \stdClass();
    $language = new \stdClass();

    yield 'userCreate' => ['userCreate', [$user], 'userCreate'];
    yield 'userDelete' => ['userDelete', [$user], 'userDelete'];
    yield 'userAddRole' => ['userAddRole', [$user, 'admin'], 'userAddRole'];
    yield 'processBatch' => ['processBatch', [], 'processBatch'];
    yield 'watchdogFetch' => ['watchdogFetch', [5, 'php', 'error'], 'watchdogFetch'];
    yield 'cacheClear' => ['cacheClear', ['all'], 'cacheClear'];
    yield 'cacheClearStatic' => ['cacheClearStatic', [], 'cacheClearStatic'];
    yield 'nodeCreate' => ['nodeCreate', [$node], 'nodeCreate'];
    yield 'nodeDelete' => ['nodeDelete', [$node], 'nodeDelete'];
    yield 'cronRun' => ['cronRun', [], 'cronRun'];
    yield 'termCreate' => ['termCreate', [$term], 'termCreate'];
    yield 'termDelete' => ['termDelete', [$term], 'termDelete'];
    yield 'roleCreate' => ['roleCreate', [['admin']], 'roleCreate'];
    yield 'roleCreate named' => ['roleCreate', [['admin'], 'editor', 'Editor'], 'roleCreate'];
    yield 'roleDelete' => ['roleDelete', ['editor'], 'roleDelete'];
    yield 'languageCreate' => ['languageCreate', [$language], 'languageCreate'];
    yield 'languageDelete' => ['languageDelete', [$language], 'languageDelete'];
    yield 'configGet' => ['configGet', ['system.site', 'name'], 'configGet'];
    yield 'configGetOriginal' => ['configGetOriginal', ['system.site', 'name'], 'configGetOriginal'];
    yield 'configSet' => ['configSet', ['system.site', 'name', 'v'], 'configSet'];
    yield 'entityCreate' => ['entityCreate', ['node', $entity], 'entityCreate'];
    yield 'entityDelete' => ['entityDelete', ['node', $entity], 'entityDelete'];
    yield 'blockPlace' => ['blockPlace', [new \stdClass()], 'blockPlace'];
    yield 'blockDelete' => ['blockDelete', [new \stdClass()], 'blockDelete'];
    yield 'blockContentCreate' => ['blockContentCreate', [new \stdClass()], 'blockContentCreate'];
    yield 'blockContentDelete' => ['blockContentDelete', [new \stdClass()], 'blockContentDelete'];
    yield 'mailStartCollecting' => ['mailStartCollecting', [], 'mailStartCollecting'];
    yield 'mailStopCollecting' => ['mailStopCollecting', [], 'mailStopCollecting'];
    yield 'mailGet' => ['mailGet', [], 'mailGet'];
    yield 'mailClear' => ['mailClear', [], 'mailClear'];
    yield 'mailSend' => ['mailSend', ['body', 'subject', 'to@ex.co', 'en'], 'mailSend'];
    yield 'moduleInstall' => ['moduleInstall', ['node'], 'moduleInstall'];
    yield 'moduleUninstall' => ['moduleUninstall', ['node'], 'moduleUninstall'];
  }

  /**
   * Creates a 'DrupalDriver' with an injected core and a fixed version.
   *
   * Bypasses the constructor (which requires a real Drupal installation) and
   * sets the private properties directly via reflection.
   */
  protected function createDriverWithCore(CoreInterface $core, int $version = 11): DrupalDriver {
    $reflection = new \ReflectionClass(DrupalDriver::class);
    /** @var \Drupal\Driver\DrupalDriver $driver */
    $driver = $reflection->newInstanceWithoutConstructor();

    $root = $reflection->getProperty('drupalRoot');
    $root->setValue($driver, __DIR__);

    $uri = $reflection->getProperty('uri');
    $uri->setValue($driver, 'default');

    $version_property = $reflection->getProperty('version');
    $version_property->setValue($driver, $version);

    $driver->setCore($core);

    return $driver;
  }

}
