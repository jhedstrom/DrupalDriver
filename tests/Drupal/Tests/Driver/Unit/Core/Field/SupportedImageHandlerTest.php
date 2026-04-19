<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Core\Field\SupportedImageHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the SupportedImageHandler field handler.
 */
class SupportedImageHandlerTest extends TestCase {

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

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Error reading file');

    @$handler->expand('/tmp/drupal-driver-nonexistent-supported-image.jpg');
  }

  /**
   * Tests that a string input is normalised into a single-item result.
   */
  public function testExpandNormalisesStringInputToSingleItem(): void {
    $path = $this->createTempFile('jpg');
    $this->setFileRepositoryWithReturnId(3);

    $handler = $this->createHandler();

    $result = $handler->expand($path);

    $this->assertSame([
      [
        'target_id' => 3,
        'alt' => NULL,
        'title' => NULL,
        'caption_value' => NULL,
        'caption_format' => NULL,
        'attribution_value' => NULL,
        'attribution_format' => NULL,
      ],
    ], $result);
  }

  /**
   * Tests that caption and attribution metadata are preserved.
   */
  public function testExpandPreservesCaptionAndAttributionMetadata(): void {
    $path = $this->createTempFile('png');
    $this->setFileRepositoryWithReturnId(5);

    $handler = $this->createHandler();

    $result = $handler->expand([
      [
        'target_id' => $path,
        'alt' => 'Alt',
        'title' => 'Title',
        'caption_value' => 'Caption body',
        'caption_format' => 'basic_html',
        'attribution_value' => 'Photographer',
        'attribution_format' => 'plain_text',
      ],
    ]);

    $this->assertSame([
      [
        'target_id' => 5,
        'alt' => 'Alt',
        'title' => 'Title',
        'caption_value' => 'Caption body',
        'caption_format' => 'basic_html',
        'attribution_value' => 'Photographer',
        'attribution_format' => 'plain_text',
      ],
    ], $result);
  }

  /**
   * Tests that a single array with target_id is wrapped as a single item.
   */
  public function testExpandWrapsSingleKeyedArrayInput(): void {
    $path = $this->createTempFile('jpg');
    $this->setFileRepositoryWithReturnId(8);

    $handler = $this->createHandler();

    $result = $handler->expand([
      'target_id' => $path,
      'alt' => 'An image',
    ]);

    $this->assertCount(1, $result);
    $this->assertSame(8, $result[0]['target_id']);
    $this->assertSame('An image', $result[0]['alt']);
  }

  /**
   * Creates a SupportedImageHandler that bypasses the parent constructor.
   */
  protected function createHandler(): SupportedImageHandler {
    $reflection = new \ReflectionClass(SupportedImageHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

  /**
   * Creates a temporary file with the given extension.
   */
  protected function createTempFile(string $extension): string {
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
  protected function setFileRepositoryWithReturnId(int $file_id): void {
    $file = new class($file_id) {

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

    $repository = new class($file) {

      public function __construct(private readonly mixed $file) {}

      /**
       * Writes data to a destination and returns the stored file entity.
       */
      public function writeData(string $data, string $destination): mixed {
        return $this->file;
      }

    };

    $container = new ContainerBuilder();
    $container->set('file.repository', $repository);
    \Drupal::setContainer($container);
  }

}
