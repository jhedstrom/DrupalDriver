<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\SupportedImageHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the SupportedImageHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class SupportedImageHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * Absolute path to the bundled fixture file.
   */
  protected const FIXTURE_PATH = __DIR__ . '/../../../../../../fixtures/files/fixture.bin';

  /**
   * File id 'file.repository::writeData()' returns from the stub.
   */
  protected const UPLOADED_FILE_ID = 3;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
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
    $reflection = new \ReflectionClass(SupportedImageHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $main_property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_property->setValue($handler, 'target_id');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'bare scalar path produces full record' => [
      self::FIXTURE_PATH,
      [[
        'target_id' => self::UPLOADED_FILE_ID,
        'alt' => NULL,
        'title' => NULL,
        'caption_value' => NULL,
        'caption_format' => NULL,
        'attribution_value' => NULL,
        'attribution_format' => NULL,
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'record preserves caption and attribution metadata' => [
      [[
        'target_id' => self::FIXTURE_PATH,
        'alt' => 'Alt',
        'title' => 'Title',
        'caption_value' => 'Caption body',
        'caption_format' => 'basic_html',
        'attribution_value' => 'Photographer',
        'attribution_format' => 'plain_text',
      ],
      ],
      [[
        'target_id' => self::UPLOADED_FILE_ID,
        'alt' => 'Alt',
        'title' => 'Title',
        'caption_value' => 'Caption body',
        'caption_format' => 'basic_html',
        'attribution_value' => 'Photographer',
        'attribution_format' => 'plain_text',
      ],
      ],
      NULL,
      NULL,
    ];

    yield 'NULL target_id rejected' => [
      [['target_id' => NULL]],
      NULL,
      \InvalidArgumentException::class,
      'Supported image field "target_id" must not be NULL or empty.',
    ];
    yield 'unreadable path bubbles up as Exception' => [
      '/tmp/drupal-driver-nonexistent-supported-image.jpg',
      NULL,
      \Exception::class,
      'Error reading file /tmp/drupal-driver-nonexistent-supported-image.jpg.',
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

}
