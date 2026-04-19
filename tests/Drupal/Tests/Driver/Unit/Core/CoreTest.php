<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Core;

use Drupal\Core\CronInterface;
use Drupal\Driver\Core\Core;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests for the default Drupal Core class.
 */
class CoreTest extends TestCase {

  /**
   * The original REQUEST_TIME value.
   */
  protected ?int $originalRequestTime = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->originalRequestTime = $_SERVER['REQUEST_TIME'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->originalRequestTime !== NULL) {
      $_SERVER['REQUEST_TIME'] = $this->originalRequestTime;
    }
    parent::tearDown();
  }

  /**
   * Tests that `cronRun()` refreshes `REQUEST_TIME` before running cron.
   */
  public function testRunCronRefreshesRequestTime(): void {
    $before = time();
    $stale_time = $before - 60;

    // Create a real Symfony Request with a stale REQUEST_TIME.
    $request = new Request();
    $request->server->set('REQUEST_TIME', $stale_time);
    $_SERVER['REQUEST_TIME'] = $stale_time;

    // Mock the cron service.
    $cron = $this->createMock(CronInterface::class);
    $cron->method('run')->willReturn(TRUE);

    // Wire a container with request_stack and cron service.
    $request_stack = new RequestStack();
    $request_stack->push($request);

    $container = new ContainerBuilder();
    $container->set('request_stack', $request_stack);
    $container->set('cron', $cron);
    \Drupal::setContainer($container);

    // Use __DIR__ as a dummy drupal root (cronRun does not use it).
    $core = new Core(__DIR__, 'default');
    $result = $core->cronRun();

    $this->assertTrue($result);
    $this->assertGreaterThanOrEqual($before, $_SERVER['REQUEST_TIME'], '$_SERVER[REQUEST_TIME] was not refreshed.');
    $this->assertGreaterThanOrEqual($before, $request->server->get('REQUEST_TIME'), 'Request server bag REQUEST_TIME was not refreshed.');
  }

}
