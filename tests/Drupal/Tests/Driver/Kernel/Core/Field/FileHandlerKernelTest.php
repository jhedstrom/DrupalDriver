<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\file\Entity\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for FileHandler via the Core driver.
 *
 * FileHandler reads a source file from disk, writes it into public:// via the
 * file.repository service, saves a managed File entity, and emits a reference
 * payload (target_id, display, description). This kernel test runs the whole
 * chain against real storage and asserts the reference round-trips.
 *
 * @group fields
 */
#[Group('fields')]
class FileHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    // Point public:// at a real, writable directory under the test site.
    $public_path = $this->siteDirectory . '/files';
    if (!is_dir($public_path)) {
      mkdir($public_path, 0777, TRUE);
    }
    $this->setSetting('file_public_path', $public_path);
  }

  /**
   * Tests round-trip for a file field with a source file from disk.
   */
  public function testFileRoundTrip(): void {
    $this->attachField('field_attachment', 'file');

    $fixture = dirname(__DIR__, 6) . '/fixtures/files/sample.txt';

    $this->assertFieldRoundTripViaDriver('field_attachment', [$fixture]);

    // Sanity: the file entity the handler created is actually loadable.
    $file_id = $this->latestFileId();
    $this->assertInstanceOf(File::class, File::load($file_id));
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
