<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\file\Entity\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for ImageHandler via the Core driver.
 *
 * ImageHandler reads an image file, writes it to public:// via the
 * file.repository service, and emits a single-delta shorthand
 * ['target_id' => X, 'alt' => Y, 'title' => Z]. The base helper's
 * normalisation handles that shape, so the assertion is identical to the
 * other multi-property handlers.
 */
#[Group('fields')]
class ImageHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'file',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $public_path = $this->siteDirectory . '/files';
    if (!is_dir($public_path)) {
      mkdir($public_path, 0777, TRUE);
    }
    $this->setSetting('file_public_path', $public_path);
  }

  /**
   * Tests round-trip for an image field with a source JPEG from disk.
   */
  public function testImageRoundTrip(): void {
    $this->attachField('field_photo', 'image');

    $fixture = dirname(__DIR__, 6) . '/fixtures/files/sample.jpg';

    // ImageHandler takes [$path] or [$path, 'alt' => ..., 'title' => ...].
    // The alt/title keys come in as associative keys beside the numeric path.
    $this->assertFieldRoundTripViaDriver('field_photo', [
      0 => $fixture,
      'alt' => 'A red pixel.',
      'title' => 'Sample photo.',
    ]);

    $this->assertInstanceOf(File::class, File::load($this->latestFileId()));
  }

  /**
   * Returns the highest file id currently in storage.
   */
  protected function latestFileId(): int {
    $ids = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('fid', 'DESC')
      ->range(0, 1)
      ->execute();

    return (int) reset($ids);
  }

}
