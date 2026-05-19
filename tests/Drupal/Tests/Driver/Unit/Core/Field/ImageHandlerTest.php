<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\ImageHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the ImageHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class ImageHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * Absolute path to the bundled fixture file.
   */
  protected const FIXTURE_PATH = __DIR__ . '/../../../../../../fixtures/files/fixture.bin';

  /**
   * File id 'file.repository::writeData()' returns from the upload-path stub.
   */
  protected const UPLOADED_FILE_ID = 7;

  /**
   * Storage stub maps these URIs to file ids for the reuse path.
   *
   * @var array<string, int>
   */
  protected const REGISTERED_FILES = [
    'public://hero.jpg' => 55,
    'public://logo.png' => 66,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->createEntityTypeManager(self::REGISTERED_FILES));
    $container->set('file.repository', $this->createFileRepository(self::UPLOADED_FILE_ID));
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $reflection = new \ReflectionClass(ImageHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $main_property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_property->setValue($handler, 'target_id');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'bare scalar path triggers upload' => [
      self::FIXTURE_PATH,
      [['target_id' => self::UPLOADED_FILE_ID, 'alt' => NULL, 'title' => NULL]],
      NULL,
      NULL,
    ];
    yield 'list of paths triggers upload' => [
      [self::FIXTURE_PATH, self::FIXTURE_PATH],
      [
        ['target_id' => self::UPLOADED_FILE_ID, 'alt' => NULL, 'title' => NULL],
        ['target_id' => self::UPLOADED_FILE_ID, 'alt' => NULL, 'title' => NULL],
      ],
      NULL,
      NULL,
    ];
    yield 'record with alt and title preserved' => [
      [['target_id' => self::FIXTURE_PATH, 'alt' => 'An image', 'title' => 'A title']],
      [['target_id' => self::UPLOADED_FILE_ID, 'alt' => 'An image', 'title' => 'A title']],
      NULL,
      NULL,
    ];
    yield 'known URI reuses managed file' => [
      ['public://hero.jpg'],
      [['target_id' => 55, 'alt' => NULL, 'title' => NULL]],
      NULL,
      NULL,
    ];
    yield 'bare basename resolves under public scheme' => [
      ['logo.png'],
      [['target_id' => 66, 'alt' => NULL, 'title' => NULL]],
      NULL,
      NULL,
    ];
    yield 'record reusing managed file by URI with extras' => [
      [['target_id' => 'public://hero.jpg', 'alt' => 'Hero', 'title' => 'Hero title']],
      [['target_id' => 55, 'alt' => 'Hero', 'title' => 'Hero title']],
      NULL,
      NULL,
    ];

    yield 'NULL target_id rejected' => [
      [['target_id' => NULL, 'alt' => 'A']],
      NULL,
      \InvalidArgumentException::class,
      'Image field "target_id" must not be NULL or empty.',
    ];
    yield 'empty target_id rejected' => [
      [['target_id' => '', 'alt' => 'A']],
      NULL,
      \InvalidArgumentException::class,
      'Image field "target_id" must not be NULL or empty.',
    ];
    yield 'unreadable path bubbles up as Exception' => [
      ['/tmp/drupal-driver-nonexistent-image.jpg'],
      NULL,
      \Exception::class,
      'Error reading file /tmp/drupal-driver-nonexistent-image.jpg.',
    ];
  }

  /**
   * Builds a fake File entity exposing 'id()'.
   */
  protected static function createFakeFile(int $id): object {
    return new class($id) {

      public function __construct(protected readonly int $id) {}

      /**
       * Returns the configured file entity id.
       */
      public function id(): int {
        return $this->id;
      }

      /**
       * Saves the file entity (no-op in the test double).
       */
      public function save(): void {
      }

    };
  }

  /**
   * Builds a file.repository stub returning a fresh File on writeData().
   */
  protected function createFileRepository(int $upload_id): object {
    $file = self::createFakeFile($upload_id);

    return new class($file) {

      public function __construct(protected readonly object $file) {}

      /**
       * Returns the configured file entity for any write.
       */
      public function writeData(string $data, string $destination): object {
        return $this->file;
      }

    };
  }

  /**
   * Builds an entity_type.manager stub for the file storage lookup branch.
   *
   * @param array<string, int> $registered_files
   *   Map of URI to file id; anything outside the map produces no match.
   */
  protected function createEntityTypeManager(array $registered_files): object {
    $files_by_uri = [];

    foreach ($registered_files as $uri => $id) {
      $files_by_uri[$uri] = self::createFakeFile($id);
    }

    $storage = new class($files_by_uri) {

      /**
       * @param array<string, object> $files_by_uri
       *   Files keyed by URI.
       */
      public function __construct(protected readonly array $files_by_uri) {}

      /**
       * Returns the file matching the given URI, or an empty list.
       *
       * @param array<string, string> $properties
       *   Lookup properties keyed by name.
       *
       * @return array<int, object>
       *   Single-element list when matched, empty otherwise.
       */
      public function loadByProperties(array $properties): array {
        $uri = $properties['uri'] ?? NULL;

        return $uri !== NULL && isset($this->files_by_uri[$uri])
          ? [$this->files_by_uri[$uri]]
          : [];
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
