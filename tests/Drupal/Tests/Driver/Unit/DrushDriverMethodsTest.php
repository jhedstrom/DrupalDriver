<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit;

use Drupal\Component\Utility\Random;
use Drupal\Driver\DrushDriver;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

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
   * Tests that 'bootstrap()' flips the bootstrapped flag.
   */
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
    $driver->drushResponse = "12.5.2.0\n";
    $driver->bootstrap();
    $driver->invocations = [];

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
    $driver->drushResponse = "8.4.12\n";
    $driver->bootstrap();
    $driver->invocations = [];

    $driver->cacheClear('all');

    $this->assertSame('cache-clear', $driver->invocations[0]['command']);
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
   * Tests that 'fieldExists()' returns FALSE when the Drush call throws.
   */
  public function testFieldExistsReturnsFalseOnFailure(): void {
    $driver = $this->createDriver();
    $driver->drushThrows = TRUE;

    $this->assertFalse($driver->fieldExists('node', 'title'));
  }

  /**
   * Tests that 'fieldIsBase()' always returns FALSE.
   */
  public function testFieldIsBaseReturnsFalse(): void {
    $driver = $this->createDriver();

    $this->assertFalse($driver->fieldIsBase('node', 'title'));
  }

  /**
   * Tests 'nodeCreate()' looks up the author uid when 'author' is set.
   */
  public function testNodeCreateWithAuthorLooksUpUid(): void {
    $driver = new AuthorAwareDrushDriver('alias');
    $driver->userInfoResponse = "User ID   :   42\nUser name :   alice\n";
    $driver->nodeCreateResponse = '{"nid":1,"uid":42}';

    $node = (object) ['type' => 'article', 'author' => 'alice'];
    $driver->nodeCreate($node);

    $this->assertSame('user-information', $driver->invocations[0]['command']);
    $this->assertSame('behat', $driver->invocations[1]['command']);
    $this->assertSame(42, $node->uid);
  }

  /**
   * Tests 'userCreate()' applies roles when the user object declares them.
   */
  public function testUserCreateWithRolesInvokesRoleAssignment(): void {
    $driver = $this->createDriver();
    $driver->drushResponse = "User ID   :   7\nUser name :   bob\n";

    $user = (object) [
      'name' => 'bob',
      'pass' => 'pw',
      'mail' => 'bob@ex.co',
      'roles' => ['editor', 'reviewer'],
    ];
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
    $driver->drushResponse = "12.5.2.0\n";
    $driver->bootstrap();
    $driver->invocations = [];

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
    $user = (object) ['name' => 'alice', 'pass' => 'pw', 'mail' => 'alice@ex.co'];
    $node = (object) ['type' => 'article', 'title' => 'Hello'];
    $term = (object) ['name' => 'Tag', 'vocabulary_machine_name' => 'tags'];
    $entity = (object) ['name' => 'X'];

    yield 'userCreate' => ['userCreate', [$user], 'user-create'];
    yield 'userDelete' => ['userDelete', [$user], 'user-cancel'];
    yield 'userAddRole' => ['userAddRole', [$user, 'admin'], 'user-add-role'];
    yield 'nodeCreate' => ['nodeCreate', [$node], 'behat', '{"nid":1}'];
    yield 'nodeDelete' => ['nodeDelete', [$node], 'behat'];
    yield 'termCreate' => ['termCreate', [$term], 'behat', '{"tid":1}'];
    yield 'termDelete' => ['termDelete', [$term], 'behat'];
    yield 'entityCreate' => ['entityCreate', ['node', $entity], 'behat', '{"id":1}'];
    yield 'entityDelete' => ['entityDelete', ['node', $entity], 'behat'];
    yield 'watchdogFetch' => ['watchdogFetch', [10], 'watchdog-show'];
    yield 'cronRun' => ['cronRun', [], 'cron'];
    yield 'moduleInstall' => ['moduleInstall', ['dblog'], 'pm-enable'];
    yield 'moduleUninstall' => ['moduleUninstall', ['dblog'], 'pm-uninstall'];
    yield 'fieldExists' => ['fieldExists', ['node', 'title'], 'behat', "true\n"];
    yield 'configGet' => ['configGet', ['system.site', 'name'], 'config:get', '"Example"'];
    yield 'configGetOriginal' => ['configGetOriginal', ['system.site'], 'config:get', '{}'];
    yield 'configSet' => ['configSet', ['system.site', 'name', 'v'], 'config:set'];
    yield 'roleCreate no permissions' => ['roleCreate', [[]], 'role:create'];
    yield 'roleCreate with permissions' => ['roleCreate', [['access content']], 'role:create'];
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

/**
 * Subclass of 'RecordingDrushDriver' that returns distinct responses per call.
 *
 * Used for paths that call 'drush()' more than once (e.g. 'nodeCreate()' with
 * an 'author' that first triggers a 'user-information' lookup then a
 * 'create-node' call).
 */
class AuthorAwareDrushDriver extends RecordingDrushDriver {

  /**
   * Response for the user-information call.
   */
  public string $userInfoResponse = '';

  /**
   * Response for the create-node call.
   */
  public string $nodeCreateResponse = '';

  /**
   * {@inheritdoc}
   */
  public function drush(string $command, array $arguments = [], array $options = []): string {
    $this->invocations[] = [
      'command' => $command,
      'arguments' => $arguments,
      'options' => $options,
    ];

    if ($command === 'user-information') {
      return $this->userInfoResponse;
    }

    return $this->nodeCreateResponse;
  }

}
