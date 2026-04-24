<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Core\Field\FileHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the FileHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
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
  public function testExpandThrowsWhenFileCannotBeRead(): void {
    $handler = $this->createHandler();
    $this->setServicesWithNoMatchingFile();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Error reading file /tmp/drupal-driver-nonexistent-file.bin.');

    @$handler->expand(['/tmp/drupal-driver-nonexistent-file.bin']);
  }

  /**
   * Tests absolute disk paths fall through to upload when no match exists.
   */
  public function testExpandHandlesStringValueWithDefaults(): void {
    $path = $this->createTempFile('png');
    $this->setServicesWithUploadReturnId(99);

    $handler = $this->createHandler();

    $result = $handler->expand([$path]);

    $this->assertSame([
      ['target_id' => 99, 'display' => 1, 'description' => ''],
    ], $result);
  }

  /**
   * Tests that keyed array values honour their explicit overrides.
   */
  public function testExpandHandlesArrayValueWithOverrides(): void {
    $path = $this->createTempFile('pdf');
    $this->setServicesWithUploadReturnId(42);

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
   * Tests that a full URI input reuses an existing managed File if present.
   *
   * Restored 2.x behaviour: passing 'public://logo.png' must not trigger a
   * new upload when a managed File already points at that URI.
   */
  public function testExpandReusesExistingManagedFileByUri(): void {
    $this->setServicesWithMatchingManagedFile(
      uri: 'public://logo.png',
      file_id: 77,
    );

    $handler = $this->createHandler();

    $result = $handler->expand(['public://logo.png']);

    $this->assertSame([
      ['target_id' => 77, 'display' => 1, 'description' => ''],
    ], $result);
  }

  /**
   * Tests that a bare basename reuses an existing managed File via public://.
   */
  public function testExpandReusesExistingManagedFileByBareBasenamePublic(): void {
    $this->setServicesWithMatchingManagedFile(
      uri: 'public://report.pdf',
      file_id: 123,
    );

    $handler = $this->createHandler();

    $result = $handler->expand(['report.pdf']);

    $this->assertSame([
      ['target_id' => 123, 'display' => 1, 'description' => ''],
    ], $result);
  }

  /**
   * Tests that a bare basename falls back to private:// when public:// misses.
   */
  public function testExpandReusesExistingManagedFileByBareBasenamePrivate(): void {
    $this->setServicesWithMatchingManagedFile(
      uri: 'private://secret.pdf',
      file_id: 444,
    );

    $handler = $this->createHandler();

    $result = $handler->expand(['secret.pdf']);

    $this->assertSame([
      ['target_id' => 444, 'display' => 1, 'description' => ''],
    ], $result);
  }

  /**
   * Creates a FileHandler that bypasses the parent constructor.
   */
  protected function createHandler(): FileHandler {
    $reflection = new \ReflectionClass(FileHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

  /**
   * Creates a temporary file and returns its path.
   */
  protected function createTempFile(string $extension): string {
    $path = tempnam(sys_get_temp_dir(), 'drupal-driver-') . '.' . $extension;
    file_put_contents($path, 'fixture');
    return $path;
  }

  /**
   * Sets up services such that no existing managed file matches any lookup.
   */
  protected function setServicesWithNoMatchingFile(): void {
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->createEntityTypeManagerReturningNoMatches());
    \Drupal::setContainer($container);
  }

  /**
   * Sets up services for the upload-path branch.
   *
   * No managed file matches the lookup; file.repository returns a new File
   * with the given ID.
   */
  protected function setServicesWithUploadReturnId(int $file_id): void {
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->createEntityTypeManagerReturningNoMatches());
    $container->set('file.repository', $this->createFileRepositoryReturning($this->createFakeFile($file_id)));
    \Drupal::setContainer($container);
  }

  /**
   * Sets up services for the resolve-path branch: managed file matches at URI.
   */
  protected function setServicesWithMatchingManagedFile(string $uri, int $file_id): void {
    $container = new ContainerBuilder();
    $container->set(
      'entity_type.manager',
      $this->createEntityTypeManagerReturningFileAtUri($uri, $this->createFakeFile($file_id)),
    );
    \Drupal::setContainer($container);
  }

  /**
   * Creates a stand-in File entity that exposes an id() method.
   */
  private function createFakeFile(int $file_id): object {
    return new class($file_id) {

      public function __construct(private readonly int $file_id) {}

      /**
       * Returns the stored file entity ID.
       */
      public function id(): int {
        return $this->file_id;
      }

      /**
       * Saves the file entity (no-op in the test double).
       */
      public function save(): void {
      }

    };
  }

  /**
   * Creates a stand-in file.repository service returning the given file.
   */
  private function createFileRepositoryReturning(object $file): object {
    return new class($file) {

      public function __construct(private readonly object $file) {}

      /**
       * Returns the pre-configured stored file.
       */
      public function writeData(string $data, string $destination): object {
        return $this->file;
      }

    };
  }

  /**
   * Creates a stand-in entity_type.manager whose file storage never matches.
   */
  private function createEntityTypeManagerReturningNoMatches(): object {
    $storage = new class {

      /**
       * Returns an empty match list for every lookup.
       *
       * @param array<string, string> $properties
       *   The lookup properties (ignored in this stub).
       *
       * @return array<int, object>
       *   Always an empty array.
       */
      public function loadByProperties(array $properties): array {
        return [];
      }

    };

    return new class($storage) {

      public function __construct(private readonly object $storage) {}

      /**
       * Returns the stub file storage.
       */
      public function getStorage(string $entity_type_id): object {
        return $this->storage;
      }

    };
  }

  /**
   * Creates an entity_type.manager stub whose file storage matches one URI.
   *
   * The storage's loadByProperties() returns $file only when called with
   * exactly ['uri' => $uri], and an empty array for every other lookup.
   */
  private function createEntityTypeManagerReturningFileAtUri(string $uri, object $file): object {
    $storage = new class($uri, $file) {

      public function __construct(private readonly string $uri, private readonly object $file) {}

      /**
       * Returns the configured file only for lookups matching the stored URI.
       *
       * @param array<string, string> $properties
       *   The loadByProperties() input keyed by property name.
       *
       * @return array<int, object>
       *   Either a single-element list with the configured file, or empty.
       */
      public function loadByProperties(array $properties): array {
        if (($properties['uri'] ?? NULL) === $this->uri) {
          return [$this->file];
        }

        return [];
      }

    };

    return new class($storage) {

      public function __construct(private readonly object $storage) {}

      /**
       * Returns the stub file storage.
       */
      public function getStorage(string $entity_type_id): object {
        return $this->storage;
      }

    };
  }

}
