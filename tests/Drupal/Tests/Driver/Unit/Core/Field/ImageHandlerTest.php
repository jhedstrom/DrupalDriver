<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Core\Field\ImageHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

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
   * Tests that a readable path is expanded into an image field value.
   */
  public function testExpandReturnsImageValueWithDefaultAltAndTitle(): void {
    $path = tempnam(sys_get_temp_dir(), 'drupal-driver-') . '.jpg';
    file_put_contents($path, 'fixture');
    $this->setServicesWithUploadReturnId(7);

    $handler = $this->createHandler();

    $result = $handler->expand([$path]);

    $this->assertSame(['target_id' => 7, 'alt' => NULL, 'title' => NULL], $result);
  }

  /**
   * Tests that alt and title extras are propagated when provided.
   */
  public function testExpandPropagatesAltAndTitleExtras(): void {
    $path = tempnam(sys_get_temp_dir(), 'drupal-driver-') . '.jpg';
    file_put_contents($path, 'fixture');
    $this->setServicesWithUploadReturnId(12);

    $handler = $this->createHandler();

    $values = [$path, 'alt' => 'Alt text', 'title' => 'Title text'];
    $result = $handler->expand($values);

    $this->assertSame(['target_id' => 12, 'alt' => 'Alt text', 'title' => 'Title text'], $result);
  }

  /**
   * Tests that a full URI reuses an existing managed image file.
   */
  public function testExpandReusesExistingManagedImageByUri(): void {
    $this->setServicesWithMatchingManagedFile(
      uri: 'public://hero.jpg',
      file_id: 55,
    );

    $handler = $this->createHandler();

    $result = $handler->expand(['public://hero.jpg', 'alt' => 'Hero', 'title' => 'Hero title']);

    $this->assertSame(['target_id' => 55, 'alt' => 'Hero', 'title' => 'Hero title'], $result);
  }

  /**
   * Tests that a bare basename reuses an existing managed image file.
   */
  public function testExpandReusesExistingManagedImageByBareBasename(): void {
    $this->setServicesWithMatchingManagedFile(
      uri: 'public://logo.png',
      file_id: 66,
    );

    $handler = $this->createHandler();

    $result = $handler->expand(['logo.png']);

    $this->assertSame(['target_id' => 66, 'alt' => NULL, 'title' => NULL], $result);
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
