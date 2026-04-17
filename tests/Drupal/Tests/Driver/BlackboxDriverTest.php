<?php

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
  public function testIsBootstrappedReturnsTrue() {
    $driver = new BlackboxDriver();
    $this->assertTrue($driver->isBootstrapped());
  }

  /**
   * Tests that bootstrap() is a no-op.
   */
  public function testBootstrapIsNoop() {
    $driver = new BlackboxDriver();
    $this->assertNull($driver->bootstrap());
  }

  /**
   * Tests that isField() always returns FALSE in the blackbox driver.
   */
  public function testIsFieldReturnsFalse() {
    $driver = new BlackboxDriver();
    $this->assertFalse($driver->isField('node', 'field_body'));
  }

  /**
   * Tests that isBaseField() always returns FALSE in the blackbox driver.
   */
  public function testIsBaseFieldReturnsFalse() {
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
  public function testUnsupportedActionsThrow($method, array $args, $message_fragment) {
    $driver = new BlackboxDriver();

    $this->expectException(UnsupportedDriverActionException::class);
    $this->expectExceptionMessageMatches('/' . preg_quote($message_fragment, '/') . '/');

    $driver->$method(...$args);
  }

  /**
   * Data provider listing every BaseDriver method that must be unsupported.
   */
  public static function dataProviderUnsupportedActionsThrow() {
    $user = new \stdClass();
    $term = new \stdClass();
    $entity = new \stdClass();

    return [
      'getRandom' => ['getRandom', [], 'generate random'],
      'userCreate' => ['userCreate', [$user], 'create users'],
      'userDelete' => ['userDelete', [$user], 'delete users'],
      'processBatch' => ['processBatch', [], 'process batch actions'],
      'userAddRole' => ['userAddRole', [$user, 'editor'], 'add roles'],
      'fetchWatchdog' => ['fetchWatchdog', [], 'access watchdog entries'],
      'clearCache' => ['clearCache', [], 'clear Drupal caches'],
      'clearStaticCaches' => ['clearStaticCaches', [], 'clear static caches'],
      'createNode' => ['createNode', [new \stdClass()], 'create nodes'],
      'nodeDelete' => ['nodeDelete', [new \stdClass()], 'delete nodes'],
      'runCron' => ['runCron', [], 'run cron'],
      'createTerm' => ['createTerm', [$term], 'create terms'],
      'termDelete' => ['termDelete', [$term], 'delete terms'],
      'roleCreate' => ['roleCreate', [[]], 'create roles'],
      'roleDelete' => ['roleDelete', [1], 'delete roles'],
      'configGet' => ['configGet', ['system.site', 'name'], 'config get'],
      'configSet' => ['configSet', ['system.site', 'name', 'v'], 'config set'],
      'createEntity' => ['createEntity', ['node', $entity], 'create entities using the generic Entity API'],
      'entityDelete' => ['entityDelete', ['node', $entity], 'delete entities using the generic Entity API'],
      'startCollectingMail' => ['startCollectingMail', [], 'work with mail'],
      'stopCollectingMail' => ['stopCollectingMail', [], 'work with mail'],
      'getMail' => ['getMail', [], 'work with mail'],
      'clearMail' => ['clearMail', [], 'work with mail'],
      'sendMail' => ['sendMail', ['body', 'subject', 'to', 'en'], 'work with mail'],
      'moduleInstall' => ['moduleInstall', ['node'], 'install modules'],
      'moduleUninstall' => ['moduleUninstall', ['node'], 'uninstall modules'],
    ];
  }

}
