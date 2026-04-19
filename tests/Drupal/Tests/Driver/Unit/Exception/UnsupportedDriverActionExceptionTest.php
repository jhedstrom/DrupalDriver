<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Exception;

use Drupal\Driver\DriverInterface;
use Drupal\Driver\Exception\UnsupportedDriverActionException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the UnsupportedDriverActionException.
 *
 * @group exception
 */
#[Group('exception')]
class UnsupportedDriverActionExceptionTest extends TestCase {

  /**
   * Tests that the message template is populated with the driver class name.
   */
  public function testMessageFormatting(): void {
    $driver = $this->createMock(DriverInterface::class);
    $driver_class = $driver::class;

    $exception = new UnsupportedDriverActionException('Action %s is not supported.', $driver);

    $this->assertSame(sprintf('Action %s is not supported.', $driver_class), $exception->getMessage());
  }

  /**
   * Tests that the driver is accessible via getDriver().
   */
  public function testGetDriverReturnsConstructorArgument(): void {
    $driver = $this->createMock(DriverInterface::class);

    $exception = new UnsupportedDriverActionException('%s', $driver);

    $this->assertSame($driver, $exception->getDriver());
  }

  /**
   * Tests that code and previous exception are propagated to the parent.
   */
  public function testCodeAndPreviousArePropagated(): void {
    $driver = $this->createMock(DriverInterface::class);
    $previous = new \RuntimeException('root cause');

    $exception = new UnsupportedDriverActionException('%s', $driver, 42, $previous);

    $this->assertSame(42, $exception->getCode());
    $this->assertSame($previous, $exception->getPrevious());
  }

}
