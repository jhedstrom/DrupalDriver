<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Core\Field\ImageHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the ImageHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class ImageHandlerTest extends TestCase {

  /**
   * {@inheritdoc}
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
    $this->expectExceptionMessage('Error reading file /tmp/drupal-driver-nonexistent-image.jpg.');

    @$handler->expand(['/tmp/drupal-driver-nonexistent-image.jpg']);
  }

  /**
   * Tests every accepted input shape on the upload code path.
   *
   * Covers the three shapes the handler is contracted to accept:
   *   - Scalar mode from EntityFieldParser ('['foo.jpg']').
   *   - Legacy flat-positional shape kept for back-compat ('['foo.jpg', 'alt' => ...]').
   *   - Compound mode from EntityFieldParser row 16
   *     ('[['target_id' => 'foo.jpg', ...]]') including multi-record input.
   *
   * @param \Closure(string): array<int|string, mixed> $build_input
   *   Builds the input array given the temp file path.
   * @param array<int|string, mixed> $expected
   *   The expected expand() output.
   *
   * @see \Drupal\DrupalExtension\Parser\EntityFieldParser
   */
  #[DataProvider('dataProviderExpandUploadShapes')]
  public function testExpandUploadsFile(\Closure $build_input, array $expected): void {
    $path = tempnam(sys_get_temp_dir(), 'drupal-driver-') . '.jpg';
    file_put_contents($path, 'fixture');
    $this->setServicesWithUploadReturnId(7);

    $handler = $this->createHandler();

    $result = $handler->expand($build_input($path));

    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpandUploadsFile().
   */
  public static function dataProviderExpandUploadShapes(): \Iterator {
    yield 'scalar single' => [
      static fn (string $path) => [$path],
      ['target_id' => 7, 'alt' => NULL, 'title' => NULL],
    ];
    yield 'legacy flat positional with extras' => [
      static fn (string $path) => [$path, 'alt' => 'Alt text', 'title' => 'Title text'],
      ['target_id' => 7, 'alt' => 'Alt text', 'title' => 'Title text'],
    ];
    yield 'compound single, bare target_id' => [
      static fn (string $path) => [['target_id' => $path]],
      ['target_id' => 7, 'alt' => NULL, 'title' => NULL],
    ];
    yield 'compound single with alt and title' => [
      static fn (string $path) => [['target_id' => $path, 'alt' => 'An image', 'title' => 'A title']],
      ['target_id' => 7, 'alt' => 'An image', 'title' => 'A title'],
    ];
    yield 'compound multi-record' => [
      static fn (string $path) => [
        ['target_id' => $path, 'alt' => 'First'],
        ['target_id' => $path, 'alt' => 'Second', 'title' => 'Second title'],
      ],
      [
        ['target_id' => 7, 'alt' => 'First', 'title' => NULL],
        ['target_id' => 7, 'alt' => 'Second', 'title' => 'Second title'],
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
   * @param array<int|string, mixed> $expected
   *   The expected expand() output.
   */
  #[DataProvider('dataProviderExpandReuseShapes')]
  public function testExpandReusesManagedFile(string $managed_uri, int $file_id, array $input, array $expected): void {
    $this->setServicesWithMatchingManagedFile(uri: $managed_uri, file_id: $file_id);

    $handler = $this->createHandler();

    $result = $handler->expand($input);

    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpandReusesManagedFile().
   */
  public static function dataProviderExpandReuseShapes(): \Iterator {
    yield 'scalar uri reuse' => [
      'public://hero.jpg',
      55,
      ['public://hero.jpg', 'alt' => 'Hero', 'title' => 'Hero title'],
      ['target_id' => 55, 'alt' => 'Hero', 'title' => 'Hero title'],
    ];
    yield 'scalar bare basename reuse' => [
      'public://logo.png',
      66,
      ['logo.png'],
      ['target_id' => 66, 'alt' => NULL, 'title' => NULL],
    ];
    yield 'compound parser shape, uri reuse' => [
      'public://hero.jpg',
      77,
      [['target_id' => 'public://hero.jpg', 'alt' => 'Hero', 'title' => 'Hero title']],
      ['target_id' => 77, 'alt' => 'Hero', 'title' => 'Hero title'],
    ];
    yield 'compound parser shape, bare basename reuse' => [
      'public://logo.png',
      88,
      [['target_id' => 'logo.png']],
      ['target_id' => 88, 'alt' => NULL, 'title' => NULL],
    ];
  }

  /**
   * Creates an ImageHandler that bypasses the parent constructor.
   */
  protected function createHandler(): ImageHandler {
    $reflection = new \ReflectionClass(ImageHandler::class);
    return $reflection->newInstanceWithoutConstructor();
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
   */
  protected function setServicesWithUploadReturnId(int $file_id): void {
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->createEntityTypeManagerReturningNoMatches());
    $container->set('file.repository', $this->createFileRepositoryReturning($this->createFakeFile($file_id)));
    \Drupal::setContainer($container);
  }

  /**
   * Sets up services for the resolve-path branch.
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
