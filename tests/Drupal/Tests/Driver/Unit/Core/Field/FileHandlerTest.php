<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Core\Field\FileHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

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
   * Tests every accepted input shape on the upload code path.
   *
   * Covers the parser shapes that 'EntityFieldParser' emits:
   *   - Scalar single ('['foo.bin']') and scalar multi-value.
   *   - Compound mode (['target_id' => 'foo.bin', 'display' => 1, ...]).
   *
   * @param \Closure(string): array<int|string, mixed> $build_input
   *   Builds the input array given the temp file path.
   * @param array<int, array<string, mixed>> $expected
   *   The expected expand() output (always a list of records for FileHandler).
   *
   * @dataProvider dataProviderExpandUploadsFile
   */
  #[DataProvider('dataProviderExpandUploadsFile')]
  public function testExpandUploadsFile(\Closure $build_input, array $expected): void {
    $path = $this->createTempFile('pdf');
    $this->setServicesWithUploadReturnId(42);

    $handler = $this->createHandler();

    $result = $handler->expand($build_input($path));

    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpandUploadsFile().
   */
  public static function dataProviderExpandUploadsFile(): \Iterator {
    yield 'scalar single' => [
      static fn (string $path): array => [$path],
      [['target_id' => 42, 'display' => 1, 'description' => '']],
    ];
    yield 'scalar multi-value' => [
      static fn (string $path): array => [$path, $path],
      [
        ['target_id' => 42, 'display' => 1, 'description' => ''],
        ['target_id' => 42, 'display' => 1, 'description' => ''],
      ],
    ];
    yield 'compound single with display and description' => [
      static fn (string $path): array => [
        ['target_id' => $path, 'display' => 0, 'description' => 'Spec sheet'],
      ],
      [['target_id' => 42, 'display' => 0, 'description' => 'Spec sheet']],
    ];
    yield 'compound single, bare target_id' => [
      static fn (string $path): array => [['target_id' => $path]],
      [['target_id' => 42, 'display' => 1, 'description' => '']],
    ];
    yield 'compound multi-record' => [
      static fn (string $path): array => [
        ['target_id' => $path, 'display' => 1, 'description' => 'Public'],
        ['target_id' => $path, 'display' => 0, 'description' => 'Hidden'],
      ],
      [
        ['target_id' => 42, 'display' => 1, 'description' => 'Public'],
        ['target_id' => 42, 'display' => 0, 'description' => 'Hidden'],
      ],
    ];
  }

  /**
   * Tests every accepted input shape on the reuse-existing-managed-file path.
   *
   * @param string $managed_uri
   *   URI of the pre-existing managed File the storage stub will return.
   * @param int $file_id
   *   ID of the pre-existing managed File.
   * @param array<int|string, mixed> $input
   *   The input passed to expand().
   * @param array<int, array<string, mixed>> $expected
   *   The expected expand() output.
   *
   * @dataProvider dataProviderExpandReusesManagedFile
   */
  #[DataProvider('dataProviderExpandReusesManagedFile')]
  public function testExpandReusesManagedFile(string $managed_uri, int $file_id, array $input, array $expected): void {
    $this->setServicesWithMatchingManagedFile(uri: $managed_uri, file_id: $file_id);

    $handler = $this->createHandler();

    $result = $handler->expand($input);

    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpandReusesManagedFile().
   */
  public static function dataProviderExpandReusesManagedFile(): \Iterator {
    yield 'scalar full uri reuse' => [
      'public://logo.png',
      77,
      ['public://logo.png'],
      [['target_id' => 77, 'display' => 1, 'description' => '']],
    ];
    yield 'scalar bare basename, public scheme' => [
      'public://report.pdf',
      123,
      ['report.pdf'],
      [['target_id' => 123, 'display' => 1, 'description' => '']],
    ];
    yield 'scalar bare basename, private scheme fallback' => [
      'private://secret.pdf',
      444,
      ['secret.pdf'],
      [['target_id' => 444, 'display' => 1, 'description' => '']],
    ];
    yield 'compound parser shape, uri reuse' => [
      'public://logo.png',
      80,
      [['target_id' => 'public://logo.png', 'display' => 0, 'description' => 'Brand mark']],
      [['target_id' => 80, 'display' => 0, 'description' => 'Brand mark']],
    ];
    yield 'compound parser shape, bare basename reuse' => [
      'public://report.pdf',
      90,
      [['target_id' => 'report.pdf']],
      [['target_id' => 90, 'display' => 1, 'description' => '']],
    ];
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
  protected function createFakeFile(int $file_id): object {
    return new class($file_id) {

      public function __construct(protected readonly int $fileId) {}

      /**
       * Returns the stored file entity ID.
       */
      public function id(): int {
        return $this->fileId;
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
  protected function createFileRepositoryReturning(object $file): object {
    return new class($file) {

      public function __construct(protected readonly object $file) {}

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
  protected function createEntityTypeManagerReturningNoMatches(): object {
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

      public function __construct(protected readonly object $storage) {}

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
  protected function createEntityTypeManagerReturningFileAtUri(string $uri, object $file): object {
    $storage = new class($uri, $file) {

      public function __construct(protected readonly string $uri, protected readonly object $file) {}

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

      public function __construct(protected readonly object $storage) {}

      /**
       * Returns the stub file storage.
       */
      public function getStorage(string $entity_type_id): object {
        return $this->storage;
      }

    };
  }

}
