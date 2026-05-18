<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\AbstractHandler;
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

    @$handler->expand('/tmp/drupal-driver-nonexistent-image.jpg');
  }

  /**
   * Tests that records with NULL or empty 'target_id' throw clearly.
   *
   * The "key missing entirely" case is caught one layer up by
   * AbstractHandler::normalise(); this test covers the values that get
   * past key validation but cannot drive file resolution.
   *
   * @param array<int|string, mixed> $input
   *   The malformed input.
   *
   * @dataProvider dataProviderExpandThrowsWhenTargetIdInvalid
   */
  #[DataProvider('dataProviderExpandThrowsWhenTargetIdInvalid')]
  public function testExpandThrowsWhenTargetIdInvalid(array $input): void {
    $handler = $this->createHandler();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Image field "target_id" must not be NULL or empty.');

    $handler->expand($input);
  }

  /**
   * Data provider for testExpandThrowsWhenTargetIdInvalid().
   */
  public static function dataProviderExpandThrowsWhenTargetIdInvalid(): \Iterator {
    yield 'record with NULL target_id' => [
      ['target_id' => NULL, 'alt' => 'A'],
    ];
    yield 'record with empty string target_id' => [
      ['target_id' => '', 'alt' => 'A'],
    ];
    yield 'list with NULL target_id first' => [
      [['target_id' => NULL, 'alt' => 'orphan'], ['target_id' => 'foo.jpg']],
    ];
  }

  /**
   * Tests every accepted input shape on the upload code path.
   *
   * The handler delegates shape normalisation to AbstractHandler::normalise(),
   * which already has its own dedicated test. This test confirms that every
   * loose shape arrives at the file-resolution code with the expected
   * 'target_id' and that extras (alt/title) round-trip when present.
   *
   * @param \Closure(string): mixed $build_input
   *   Builds the input given the temp file path.
   * @param array<int, array<string, mixed>> $expected
   *   The expected expand() output (always a list of records).
   *
   * @dataProvider dataProviderExpandUploadsFile
   */
  #[DataProvider('dataProviderExpandUploadsFile')]
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
  public static function dataProviderExpandUploadsFile(): \Iterator {
    yield 'bare scalar path' => [
      static fn (string $path): string => $path,
      [['target_id' => 7, 'alt' => NULL, 'title' => NULL]],
    ];
    yield 'list of one scalar path' => [
      static fn (string $path): array => [$path],
      [['target_id' => 7, 'alt' => NULL, 'title' => NULL]],
    ];
    yield 'list of two scalar paths' => [
      static fn (string $path): array => [$path, $path],
      [
        ['target_id' => 7, 'alt' => NULL, 'title' => NULL],
        ['target_id' => 7, 'alt' => NULL, 'title' => NULL],
      ],
    ];
    yield 'single record with target_id only' => [
      static fn (string $path): array => ['target_id' => $path],
      [['target_id' => 7, 'alt' => NULL, 'title' => NULL]],
    ];
    yield 'single record with alt and title' => [
      static fn (string $path): array => ['target_id' => $path, 'alt' => 'An image', 'title' => 'A title'],
      [['target_id' => 7, 'alt' => 'An image', 'title' => 'A title']],
    ];
    yield 'list of one record' => [
      static fn (string $path): array => [['target_id' => $path, 'alt' => 'Solo']],
      [['target_id' => 7, 'alt' => 'Solo', 'title' => NULL]],
    ];
    yield 'list of multiple records' => [
      static fn (string $path): array => [
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
   * @param mixed $input
   *   The input passed to expand() (any of the loose shapes the consumer
   *   can naturally produce).
   * @param array<int, array<string, mixed>> $expected
   *   The expected expand() output.
   *
   * @dataProvider dataProviderExpandReusesManagedFile
   */
  #[DataProvider('dataProviderExpandReusesManagedFile')]
  public function testExpandReusesManagedFile(string $managed_uri, int $file_id, mixed $input, array $expected): void {
    $this->setServicesWithMatchingManagedFile(uri: $managed_uri, file_id: $file_id);

    $handler = $this->createHandler();

    $result = $handler->expand($input);

    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpandReusesManagedFile().
   */
  public static function dataProviderExpandReusesManagedFile(): \Iterator {
    yield 'bare scalar uri' => [
      'public://hero.jpg',
      55,
      'public://hero.jpg',
      [['target_id' => 55, 'alt' => NULL, 'title' => NULL]],
    ];
    yield 'bare scalar basename' => [
      'public://logo.png',
      66,
      'logo.png',
      [['target_id' => 66, 'alt' => NULL, 'title' => NULL]],
    ];
    yield 'single record with uri and extras' => [
      'public://hero.jpg',
      77,
      ['target_id' => 'public://hero.jpg', 'alt' => 'Hero', 'title' => 'Hero title'],
      [['target_id' => 77, 'alt' => 'Hero', 'title' => 'Hero title']],
    ];
    yield 'list of one record with basename' => [
      'public://logo.png',
      88,
      [['target_id' => 'logo.png']],
      [['target_id' => 88, 'alt' => NULL, 'title' => NULL]],
    ];
  }

  /**
   * Creates an ImageHandler with a fieldInfo stub for normalise().
   */
  protected function createHandler(): ImageHandler {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getMainPropertyName')->willReturn('target_id');

    $reflection = new \ReflectionClass(ImageHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(AbstractHandler::class, 'fieldInfo');
    $property->setValue($handler, $field_info);

    return $handler;
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
