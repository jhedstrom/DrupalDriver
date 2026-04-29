<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Component\Utility\Random;
use Drupal\Driver\DrushDriver;
use Drupal\Driver\Entity\EntityStub;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Exercises every 'DrushDriver' public method to guarantee line coverage.
 *
 * Each test replaces the 'drush()' method with a recorder and verifies the
 * expected Drush command is invoked at least once. The actual Drush binary is
 * never executed here; end-to-end behaviour is covered separately.
 *
 * @group drivers
 * @group drush
 */
#[Group('drivers')]
#[Group('drush')]
class DrushDriverMethodsTest extends TestCase {

  /**
   * Sets 'DrushDriver::$isLegacyDrush' directly, bypassing bootstrap caching.
   */
  protected function forceLegacyDrush(bool $legacy): void {
    $reflection = new \ReflectionClass(DrushDriver::class);
    $prop = $reflection->getProperty('isLegacyDrush');
    $prop->setValue(NULL, $legacy);
  }

  /**
   * Tests that 'bootstrap()' flips the bootstrapped flag.
   *
   * Runs in its own process so the static 'isLegacyDrush' cache starts
   * uninitialised; without isolation the bootstrap's caching guard skips
   * the version-detection assignment.
   */
  #[RunInSeparateProcess]
  public function testBootstrapMarksAsBootstrapped(): void {
    $driver = $this->createDriver();
    $driver->drushResponse = "12.5.2.0\n";

    $this->assertFalse($driver->isBootstrapped());
    $driver->bootstrap();
    $this->assertTrue($driver->isBootstrapped());
  }

  /**
   * Tests that 'getRandom()' returns the random generator.
   */
  public function testGetRandomReturnsGenerator(): void {
    $driver = $this->createDriver();

    $this->assertInstanceOf(Random::class, $driver->getRandom());
  }

  /**
   * Tests that 'setArguments()' and 'getArguments()' are symmetrical.
   */
  public function testArgumentsRoundTrip(): void {
    $driver = $this->createDriver();
    $driver->setArguments('--uri=http://example.com');

    $this->assertSame('--uri=http://example.com', $driver->getArguments());
  }

  /**
   * Tests that 'processBatch()' is a no-op.
   */
  public function testProcessBatchIsNoop(): void {
    $driver = $this->createDriver();
    $driver->processBatch();

    $this->addToAssertionCount(1);
  }

  /**
   * Tests 'cacheClear()' on a modern Drush (cache:rebuild path).
   */
  public function testCacheClearOnModernDrushRebuilds(): void {
    $driver = $this->createDriver();
    $this->forceLegacyDrush(FALSE);

    $driver->cacheClear();

    $this->assertNotEmpty($driver->invocations);
    $commands = array_column($driver->invocations, 'command');
    $this->assertContains('cache:rebuild', $commands);
  }

  /**
   * Tests 'cacheClear()' on a legacy Drush (cache-clear path).
   */
  public function testCacheClearOnLegacyDrushUsesCacheClear(): void {
    $driver = $this->createDriver();
    $this->forceLegacyDrush(TRUE);

    $driver->cacheClear('all');

    $this->assertSame('cache-clear', $driver->invocations[0]['command']);
    $this->assertSame(['all'], $driver->invocations[0]['arguments']);
  }

  /**
   * Tests that 'cacheClearStatic()' is a no-op.
   */
  public function testCacheClearStaticIsNoop(): void {
    $driver = $this->createDriver();
    $driver->cacheClearStatic();

    $this->addToAssertionCount(1);
  }

  /**
   * Tests that '__call()' forwards unknown methods through 'drush()'.
   */
  public function testMagicCallForwardsToDrush(): void {
    $driver = $this->createDriver();
    $driver->drushResponse = 'magic-output';

    $result = $driver->__call('status', [['format=json']]);

    $this->assertSame('magic-output', $result);
    $this->assertNotEmpty($driver->invocations);
    $this->assertSame('status', $driver->invocations[0]['command']);
  }

  /**
   * Tests 'userCreate()' applies roles when the user object declares them.
   */
  public function testUserCreateWithRolesInvokesRoleAssignment(): void {
    $driver = $this->createDriver();
    $driver->drushResponse = "User ID   :   7\nUser name :   bob\n";

    $user = new EntityStub('user', NULL, [
      'name' => 'bob',
      'pass' => 'pw',
      'mail' => 'bob@ex.co',
      'roles' => ['editor', 'reviewer'],
    ]);
    $driver->userCreate($user);

    $commands = array_column($driver->invocations, 'command');
    $this->assertSame('user-create', $commands[0]);
    $this->assertContains('user-add-role', $commands, 'Expected a user-add-role invocation for each role.');
    $this->assertSame(2, array_count_values($commands)['user-add-role'] ?? 0);
  }

  /**
   * Tests 'cacheClear()' on legacy Drush with a drush-only bin.
   */
  public function testCacheClearDrushOnlyOnModernDrushSkipsRebuild(): void {
    $driver = $this->createDriver();
    $this->forceLegacyDrush(FALSE);

    $driver->cacheClear('drush');

    $this->assertCount(1, $driver->invocations);
    $this->assertSame('cache-clear', $driver->invocations[0]['command']);
    $this->assertSame(['drush'], $driver->invocations[0]['arguments']);
  }

  /**
   * Tests 'isLegacyDrush()' treats 'version' failure as legacy.
   */
  public function testIsLegacyDrushTreatsExceptionAsLegacy(): void {
    $driver = $this->createDriver();
    $driver->drushThrows = TRUE;

    $this->assertTrue($driver->callIsLegacyDrushWithThrowing());
  }

  /**
   * Tests that 'drush()' actually spawns the configured binary.
   *
   * Uses 'echo' as the binary so the test runs deterministically without
   * requiring a real Drush install. Echo prints the assembled command back on
   * stdout, which 'drush()' then returns.
   */
  public function testDrushExecutesBinaryAndReturnsOutput(): void {
    $echo = $this->resolveSystemBinary('echo');
    if ($echo === NULL) {
      $this->markTestSkipped('echo binary is not available on this system.');
    }

    $driver = new DrushDriver('alias', binary: $echo);

    $result = $driver->drush('version', [], ['format' => 'json']);

    $this->assertStringContainsString('@alias', $result);
    $this->assertStringContainsString('--format=json', $result);
    $this->assertStringContainsString('version', $result);
  }

  /**
   * Tests that 'drush()' falls back to stderr when stdout is empty.
   *
   * Uses 'true' as the binary (empty stdout, zero exit, empty stderr) to
   * exercise the fallback branch.
   */
  public function testDrushFallsBackToErrorOutputWhenStdoutEmpty(): void {
    $true = $this->resolveSystemBinary('true');
    if ($true === NULL) {
      $this->markTestSkipped('true binary is not available on this system.');
    }

    $driver = new DrushDriver('alias', binary: $true);

    $result = $driver->drush('version');

    $this->assertSame('', $result);
  }

  /**
   * Tests that 'drush()' emits the legacy '--nocolor' flag when set.
   */
  public function testDrushEmitsLegacyFlagWhenMarkedLegacy(): void {
    $echo = $this->resolveSystemBinary('echo');
    if ($echo === NULL) {
      $this->markTestSkipped('echo binary is not available on this system.');
    }

    $this->forceLegacyDrush(TRUE);
    $driver = new DrushDriver('alias', binary: $echo);

    $result = $driver->drush('version');

    $this->assertStringContainsString('--nocolor=1', $result);
  }

  /**
   * Tests that 'resolveProjectDrush()' picks up COMPOSER_BIN_DIR first.
   */
  public function testResolveProjectDrushPrefersComposerBin(): void {
    $temp_dir = sys_get_temp_dir() . '/drush-driver-test-' . uniqid();
    mkdir($temp_dir);
    touch($temp_dir . '/drush');
    $previous = getenv('COMPOSER_BIN_DIR');
    putenv('COMPOSER_BIN_DIR=' . $temp_dir);

    try {
      $driver = new DrushDriver('alias');
      $this->assertSame($temp_dir . '/drush', $driver->binary);
    } finally {
      putenv('COMPOSER_BIN_DIR' . ($previous === FALSE ? '' : '=' . $previous));
      unlink($temp_dir . '/drush');
      rmdir($temp_dir);
    }
  }

  /**
   * Tests that 'resolveProjectDrush()' falls back to 'vendor/bin/drush'.
   */
  public function testResolveProjectDrushFallsBackToVendorBin(): void {
    $temp_dir = sys_get_temp_dir() . '/drush-driver-cwd-' . uniqid();
    mkdir($temp_dir . '/vendor/bin', 0777, TRUE);
    touch($temp_dir . '/vendor/bin/drush');
    $previous_cwd = getcwd();
    $previous_composer = getenv('COMPOSER_BIN_DIR');
    putenv('COMPOSER_BIN_DIR');
    chdir($temp_dir);

    try {
      $driver = new DrushDriver('alias');
      $this->assertSame(getcwd() . '/vendor/bin/drush', $driver->binary);
    } finally {
      chdir($previous_cwd);
      if ($previous_composer !== FALSE) {
        putenv('COMPOSER_BIN_DIR=' . $previous_composer);
      }
      unlink($temp_dir . '/vendor/bin/drush');
      rmdir($temp_dir . '/vendor/bin');
      rmdir($temp_dir . '/vendor');
      rmdir($temp_dir);
    }
  }

  /**
   * Tests that 'drush()' throws a 'RuntimeException' on a non-zero exit.
   */
  public function testDrushThrowsRuntimeExceptionOnFailure(): void {
    $false = $this->resolveSystemBinary('false');
    if ($false === NULL) {
      $this->markTestSkipped('false binary is not available on this system.');
    }

    $driver = new DrushDriver('alias', binary: $false);

    $this->expectException(\RuntimeException::class);
    $driver->drush('version');
  }

  /**
   * Returns the first executable location for a system utility, or NULL.
   */
  protected function resolveSystemBinary(string $name): ?string {
    foreach (['/bin/' . $name, '/usr/bin/' . $name] as $candidate) {
      if (is_executable($candidate)) {
        return $candidate;
      }
    }

    return NULL;
  }

  /**
   * Tests 'parseArguments()' serialises boolean and value options.
   *
   * @param array<string, string|bool|null> $options
   *   Options passed to 'parseArguments()'.
   * @param string $expected
   *   The expected concatenated CLI option string.
   *
   * @dataProvider dataProviderParseArguments
   */
  #[DataProvider('dataProviderParseArguments')]
  public function testParseArguments(array $options, string $expected): void {
    $this->assertSame($expected, ArgumentsExposingDrushDriver::expose($options));
  }

  /**
   * Data provider for 'testParseArguments()'.
   */
  public static function dataProviderParseArguments(): \Iterator {
    yield 'empty' => [[], ''];
    yield 'single flag' => [['yes' => NULL], ' --yes'];
    yield 'single valued option' => [['format' => 'json'], ' --format=json'];
    yield 'flag and valued' => [['yes' => NULL, 'format' => 'json'], ' --yes --format=json'];
    yield 'multiple valued' => [['format' => 'json', 'root' => '/var/www'], ' --format=json --root=/var/www'];
  }

  /**
 * Tests every command-issuing method drives 'drush()' as expected.
 *
 * @param string $method
 *   The driver method name.
 * @param array<int, mixed> $args
 *   Positional arguments for the driver method.
 * @param string|null $expected_command
 *   The first Drush command string expected to be invoked.
 * @param string $drush_response
 *   Raw output returned by the stubbed 'drush()' call.
 *
 * @dataProvider dataProviderInvokesDrush
 */
  #[DataProvider('dataProviderInvokesDrush')]
  public function testInvokesDrush(string $method, array $args, ?string $expected_command, string $drush_response = ''): void {
    $driver = $this->createDriver();
    $driver->drushResponse = $drush_response;

    $driver->{$method}(...$args);

    $this->assertNotEmpty($driver->invocations, 'Expected at least one drush() invocation.');

    if ($expected_command !== NULL) {
      $this->assertSame($expected_command, $driver->invocations[0]['command']);
    }
  }

  /**
   * Data provider: method -> args -> first-expected-drush-command.
   */
  public static function dataProviderInvokesDrush(): \Iterator {
    $user = new EntityStub('user', NULL, ['name' => 'alice', 'pass' => 'pw', 'mail' => 'alice@ex.co']);

    yield 'userCreate' => ['userCreate', [$user], 'user-create'];
    yield 'userDelete' => ['userDelete', [$user], 'user-cancel'];
    yield 'userAddRole' => ['userAddRole', [$user, 'admin'], 'user-add-role'];
    yield 'watchdogFetch' => ['watchdogFetch', [10], 'watchdog-show'];
    yield 'watchdogFetch filtered' => ['watchdogFetch', [10, 'php', 'error'], 'watchdog-show'];
    yield 'cronRun' => ['cronRun', [], 'cron'];
    yield 'moduleInstall' => ['moduleInstall', ['dblog'], 'pm-enable'];
    yield 'moduleUninstall' => ['moduleUninstall', ['dblog'], 'pm-uninstall'];
    yield 'configGet' => ['configGet', ['system.site', 'name'], 'config:get', '"Example"'];
    yield 'configGetOriginal' => ['configGetOriginal', ['system.site'], 'config:get', '{}'];
    yield 'configSet' => ['configSet', ['system.site', 'name', 'v'], 'config:set'];
    yield 'roleCreate no permissions' => ['roleCreate', [[]], 'role:create'];
    yield 'roleCreate with permissions' => ['roleCreate', [['access content']], 'role:create'];
    yield 'roleCreate with explicit id' => ['roleCreate', [[], 'editor'], 'role:create'];
    yield 'roleCreate with id and label' => ['roleCreate', [['access content'], 'editor', 'Editor'], 'role:create'];
    yield 'roleDelete' => ['roleDelete', ['editor'], 'role:delete'];
  }

  /**
   * Creates a driver with a stubbed 'drush()' that records every invocation.
   */
  protected function createDriver(): RecordingDrushDriver {
    return new RecordingDrushDriver('alias');
  }

}

/**
 * Subclass of 'DrushDriver' that records every 'drush()' invocation.
 */
class RecordingDrushDriver extends DrushDriver {

  /**
   * Log of 'drush()' invocations.
   *
   * @var array<int, array{command: string, arguments: array<int, string>, options: array<string, mixed>}>
   */
  public array $invocations = [];

  /**
   * The canned response to return from stubbed 'drush()' calls.
   */
  public string $drushResponse = '';

  /**
   * When TRUE, 'drush()' throws a RuntimeException.
   */
  public bool $drushThrows = FALSE;

  /**
   * {@inheritdoc}
   */
  public function drush(string $command, array $arguments = [], array $options = []): string {
    $this->invocations[] = [
      'command' => $command,
      'arguments' => $arguments,
      'options' => $options,
    ];

    if ($this->drushThrows) {
      throw new \RuntimeException('drush stubbed failure');
    }

    return $this->drushResponse;
  }

  /**
   * Exposes 'isLegacyDrush()' for testing the exception-path coverage.
   */
  public function callIsLegacyDrushWithThrowing(): bool {
    return $this->isLegacyDrush();
  }

}

/**
 * Subclass of 'DrushDriver' that exposes the protected static parser.
 *
 * Used only by 'DrushDriverMethodsTest::testParseArguments()' to invoke the
 * protected 'parseArguments()' method directly without a Drush binary.
 */
class ArgumentsExposingDrushDriver extends DrushDriver {

  /**
   * Public wrapper over the protected static parser.
   *
   * @param array<string, string|bool|null> $arguments
   *   Argument map to serialise.
   *
   * @return string
   *   The CLI option string produced by 'parseArguments()'.
   */
  public static function expose(array $arguments): string {
    return self::parseArguments($arguments);
  }

}
