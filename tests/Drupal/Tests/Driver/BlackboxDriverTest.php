<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver;

use Drupal\Driver\BlackboxDriver;
use Drupal\Driver\Exception\UnsupportedDriverActionException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the BlackboxDriver (and by extension BaseDriver).
 */
class BlackboxDriverTest extends TestCase {

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
    $this->assertNull($driver->bootstrap());
  }

  /**
   * Tests that isField() always returns FALSE in the blackbox driver.
   */
  public function testIsFieldReturnsFalse(): void {
    $driver = new BlackboxDriver();
    $this->assertFalse($driver->isField('node', 'field_body'));
  }

  /**
   * Tests that isBaseField() always returns FALSE in the blackbox driver.
   */
  public function testIsBaseFieldReturnsFalse(): void {
    $driver = new BlackboxDriver();
    $this->assertFalse($driver->isBaseField('node', 'title'));
  }

  /**
   * Tests that unsupported driver actions throw the expected exception.
   *
   * @param string $method
   *   The BaseDriver method name to invoke.
   * @param array $args
   *   Positional arguments to pass to the method.
   * @param string $message_fragment
   *   A substring that must appear in the exception message.
   *
   * @dataProvider dataProviderUnsupportedActionsThrow
   */
  public function testUnsupportedActionsThrow(string $method, array $args, string $message_fragment): void {
    $driver = new BlackboxDriver();

    $this->expectException(UnsupportedDriverActionException::class);
    $this->expectExceptionMessageMatches('/' . preg_quote($message_fragment, '/') . '/');

    $driver->$method(...$args);
  }

  /**
   * Data provider listing every BaseDriver method that must be unsupported.
   */
  public static function dataProviderUnsupportedActionsThrow(): \Iterator {
    $user = new \stdClass();
    $term = new \stdClass();
    $entity = new \stdClass();
    yield 'getRandom' => ['getRandom', [], 'generate random'];
    yield 'userCreate' => ['userCreate', [$user], 'create users'];
    yield 'userDelete' => ['userDelete', [$user], 'delete users'];
    yield 'processBatch' => ['processBatch', [], 'process batch actions'];
    yield 'userAddRole' => ['userAddRole', [$user, 'editor'], 'add roles'];
    yield 'fetchWatchdog' => ['fetchWatchdog', [], 'access watchdog entries'];
    yield 'clearCache' => ['clearCache', [], 'clear Drupal caches'];
    yield 'clearStaticCaches' => ['clearStaticCaches', [], 'clear static caches'];
    yield 'createNode' => ['createNode', [new \stdClass()], 'create nodes'];
    yield 'nodeDelete' => ['nodeDelete', [new \stdClass()], 'delete nodes'];
    yield 'runCron' => ['runCron', [], 'run cron'];
    yield 'createTerm' => ['createTerm', [$term], 'create terms'];
    yield 'termDelete' => ['termDelete', [$term], 'delete terms'];
    yield 'roleCreate' => ['roleCreate', [[]], 'create roles'];
    yield 'roleDelete' => ['roleDelete', [1], 'delete roles'];
    yield 'configGet' => ['configGet', ['system.site', 'name'], 'config get'];
    yield 'configSet' => ['configSet', ['system.site', 'name', 'v'], 'config set'];
    yield 'createEntity' => ['createEntity', ['node', $entity], 'create entities using the generic Entity API'];
    yield 'entityDelete' => ['entityDelete', ['node', $entity], 'delete entities using the generic Entity API'];
    yield 'startCollectingMail' => ['startCollectingMail', [], 'work with mail'];
    yield 'stopCollectingMail' => ['stopCollectingMail', [], 'work with mail'];
    yield 'getMail' => ['getMail', [], 'work with mail'];
    yield 'clearMail' => ['clearMail', [], 'work with mail'];
    yield 'sendMail' => ['sendMail', ['body', 'subject', 'to', 'en'], 'work with mail'];
    yield 'moduleInstall' => ['moduleInstall', ['node'], 'install modules'];
    yield 'moduleUninstall' => ['moduleUninstall', ['node'], 'uninstall modules'];
  }

}
