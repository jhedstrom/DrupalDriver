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
   * Tests that unresolved values throw a descriptive exception.
   */
  public function testExpandThrowsWhenValueCannotBeResolved() {
    $this->setContainer(NULL, $this->createFileStorage([]));

    $handler = $this->createHandler();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Could not resolve file value "/tmp/drupal-driver-nonexistent-file.bin"');

    $handler->expand(['/tmp/drupal-driver-nonexistent-file.bin']);
  }

  /**
   * Tests that string paths produce a single target entry with defaults.
   */
  public function testExpandHandlesStringValueWithDefaults() {
    $path = $this->createTempFile('png');
    $this->setContainer($this->createFileRepositoryReturning(99), $this->createFileStorage([]));

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
    $this->setContainer($this->createFileRepositoryReturning(42), $this->createFileStorage([]));

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
   * Tests that an existing managed file is reused when looked up by filename.
   */
  public function testExpandReusesExistingManagedFileByFilename() {
    $existing = $this->createFileEntity(7);
    $this->setContainer(NULL, $this->createFileStorage([
      'filename' => ['document.pdf' => $existing],
    ]));

    $handler = $this->createHandler();

    $result = $handler->expand(['document.pdf']);

    $this->assertSame([
      ['target_id' => 7, 'display' => 1, 'description' => ''],
    ], $result);
  }

  /**
   * Tests that an existing managed file is reused when looked up by numeric id.
   */
  public function testExpandReusesExistingManagedFileByNumericId() {
    $existing = $this->createFileEntity(11);
    $this->setContainer(NULL, $this->createFileStorage([
      'id' => [11 => $existing],
    ]));

    $handler = $this->createHandler();

    $result = $handler->expand(['11']);

    $this->assertSame([
      ['target_id' => 11, 'display' => 1, 'description' => ''],
    ], $result);
  }

  /**
   * Tests that on-disk paths win over an entity that shares the filename.
   */
  public function testExpandPrefersFilesystemPathOverExistingFilename() {
    $path = $this->createTempFile('pdf');
    $existing = $this->createFileEntity(99);

    $this->setContainer(
      $this->createFileRepositoryReturning(123),
      $this->createFileStorage(['filename' => [basename($path) => $existing]])
    );

    $handler = $this->createHandler();

    $result = $handler->expand([$path]);

    $this->assertSame([
      ['target_id' => 123, 'display' => 1, 'description' => ''],
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
   * Builds a Drupal container with optional file.repository and file storage.
   *
   * Uses inline anonymous classes because FileInterface, FileRepositoryInterface
   * and the file storage handler ship with the file module rather than
   * drupal/core and are therefore not guaranteed to be autoloadable in
   * isolation.
   */
  protected function setContainer($file_repository, $file_storage) {
    $entity_type_manager = new class($file_storage) {

      public function __construct(private $file_storage) {}

      /**
       * Returns the stub file entity storage.
       */
      public function getStorage($entity_type_id) {
        return $this->file_storage;
      }

    };

    $container = new ContainerBuilder();

    if ($file_repository !== NULL) {
      $container->set('file.repository', $file_repository);
    }
    $container->set('entity_type.manager', $entity_type_manager);

    \Drupal::setContainer($container);
  }

  /**
   * Creates a file.repository stub that returns a stored file with the given id.
   */
  protected function createFileRepositoryReturning($file_id) {
    $file = $this->createFileEntity($file_id);

    return new class($file) {

      public function __construct(private $file) {}

      /**
       * Writes data to a destination and returns the stored file entity.
       */
      public function writeData($data, $destination) {
        return $this->file;
      }

    };
  }

  /**
   * Creates a file storage stub backed by id and filename indexes.
   *
   * @param array $index
   *   Index keyed by lookup type, e.g. ['id' => [id => file], 'filename' => [name => file]].
   */
  protected function createFileStorage(array $index) {
    return new class($index) {

      public function __construct(private array $index) {}

      /**
       * Loads a file entity by numeric id.
       */
      public function load($id) {
        return $this->index['id'][$id] ?? NULL;
      }

      /**
       * Loads file entities matching the given properties.
       */
      public function loadByProperties(array $properties) {
        $name = $properties['filename'] ?? NULL;
        if ($name !== NULL && isset($this->index['filename'][$name])) {
          return [$this->index['filename'][$name]];
        }

        return [];
      }

    };
  }

  /**
   * Creates a file entity stub with id() and save() methods.
   */
  protected function createFileEntity($file_id) {
    return new class($file_id) {

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
  }

}
