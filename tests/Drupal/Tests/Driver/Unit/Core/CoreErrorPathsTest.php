<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core;

use Drupal\Driver\Core\Core;
use Drupal\Driver\Exception\BootstrapException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests for standalone error branches on 'Core' that need no Drupal kernel.
 *
 * @group core
 */
#[Group('core')]
class CoreErrorPathsTest extends TestCase {

  /**
   * Tests that the constructor throws when the root path cannot be resolved.
   */
  public function testConstructorThrowsWhenRootUnresolvable(): void {
    $this->expectException(BootstrapException::class);
    $this->expectExceptionMessageMatches('/Could not resolve Drupal root/');

    new Core('/absolutely/not/a/real/path/for/drupal-driver-tests');
  }

  /**
   * Tests that 'resolveSeverityLevel()' accepts symbolic and numeric input.
   *
   * @param string $input
   *   Severity passed to 'resolveSeverityLevel()'.
   * @param int $expected
   *   Expected RFC 5424 log level.
   *
   * @dataProvider dataProviderResolveSeverityLevel
   */
  #[DataProvider('dataProviderResolveSeverityLevel')]
  public function testResolveSeverityLevel(string $input, int $expected): void {
    $core = $this->createCore();
    $reflection = new \ReflectionMethod($core, 'resolveSeverityLevel');
    $this->assertSame($expected, $reflection->invoke($core, $input));
  }

  /**
   * Data provider for 'testResolveSeverityLevel()'.
   */
  public static function dataProviderResolveSeverityLevel(): \Iterator {
    yield 'symbolic emergency' => ['emergency', 0];
    yield 'symbolic warning' => ['warning', 4];
    yield 'symbolic uppercase' => ['ERROR', 3];
    yield 'numeric string' => ['5', 5];
    yield 'numeric zero' => ['0', 0];
  }

  /**
   * Tests that 'resolveSeverityLevel()' rejects unknown severity names.
   */
  public function testResolveSeverityLevelRejectsUnknownName(): void {
    $core = $this->createCore();
    $reflection = new \ReflectionMethod($core, 'resolveSeverityLevel');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/Unknown severity level: catastrophic/');

    $reflection->invoke($core, 'catastrophic');
  }

  /**
   * Tests that 'entityCreate()' rejects an empty entity type before booting.
   *
   * The throw happens before any 'Drupal::service()' call, so no kernel is
   * needed.
   */
  public function testEntityCreateRejectsEmptyEntityType(): void {
    $core = $this->createCore();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/You must specify an entity type/');

    $core->entityCreate('', (object) []);
  }

  /**
   * Helper to build a Core instance pointed at a valid path.
   */
  protected function createCore(): Core {
    return new Core(sys_get_temp_dir());
  }

}
