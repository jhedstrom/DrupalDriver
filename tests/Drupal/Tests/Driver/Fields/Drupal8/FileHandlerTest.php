<?php

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Fields\Drupal8\FileHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the FileHandler field handler.
 */
class FileHandlerTest extends TestCase {

  /**
   * Restores the Drupal container after each test.
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * Tests that unreadable files throw a descriptive exception.
   */
  public function testExpandThrowsWhenFileCannotBeRead() {
    $handler = $this->createHandler();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Error reading file /tmp/drupal-driver-nonexistent-file.bin.');

    @$handler->expand(['/tmp/drupal-driver-nonexistent-file.bin']);
  }

  /**
   * Tests that string paths produce a single target entry with defaults.
   */
  public function testExpandHandlesStringValueWithDefaults() {
    $path = $this->createTempFile('png');
    $this->setFileRepositoryWithReturnId(99);

    $handler = $this->createHandler();

    $result = $handler->expand([$path]);

    $this->assertSame([
      ['target_id' => 99, 'display' => 1, 'description' => ''],
    ], $result);
  }

  /**
   * Tests that keyed array values honour their explicit overrides.
   */
  public function testExpandHandlesArrayValueWithOverrides() {
    $path = $this->createTempFile('pdf');
    $this->setFileRepositoryWithReturnId(42);

    $handler = $this->createHandler();

    $result = $handler->expand([
      [
        'target_id' => $path,
        'display' => 0,
        'description' => 'Spec sheet',
      ],
    ]);

    $this->assertSame([
      ['target_id' => 42, 'display' => 0, 'description' => 'Spec sheet'],
    ], $result);
  }

  /**
   * Creates a FileHandler that bypasses the parent constructor.
   */
  protected function createHandler() {
    $reflection = new \ReflectionClass(FileHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

  /**
   * Creates a temporary file and returns its path.
   */
  protected function createTempFile($extension) {
    $path = tempnam(sys_get_temp_dir(), 'drupal-driver-') . '.' . $extension;
    file_put_contents($path, 'fixture');
    return $path;
  }

  /**
   * Registers a mocked file.repository service returning a file with an ID.
   *
   * Uses inline anonymous classes because FileInterface and
   * FileRepositoryInterface ship with the file module rather than drupal/core
   * and are therefore not guaranteed to be autoloadable in isolation.
   */
  protected function setFileRepositoryWithReturnId($file_id) {
    $file = new class($file_id) {

      public function __construct(private $file_id) {}

      /**
       * Returns the stored file entity ID.
       */
      public function id() {
        return $this->file_id;
      }

      /**
       * Saves the file entity (no-op in the test double).
       */
      public function save() {
      }

    };

    $repository = new class($file) {

      public function __construct(private $file) {}

      /**
       * Writes data to a destination and returns the stored file entity.
       */
      public function writeData($data, $destination) {
        return $this->file;
      }

    };

    $container = new ContainerBuilder();
    $container->set('file.repository', $repository);
    \Drupal::setContainer($container);
  }

}
