<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

/**
 * Kernel round-trip test for SupportedImageHandler via the Core driver.
 *
 * The 'supported_image' field (provided by drupal/supported_image) adds
 * caption and attribution columns on top of the standard image file reference.
 * The handler mirrors ImageHandler's disk read/write but emits richer payload.
 */
class SupportedImageHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'file',
    'image',
    'filter',
    'text',
    'supported_image',
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
   * Tests round-trip for a supported_image field with a disk source image.
   */
  public function testSupportedImageRoundTrip(): void {
    $this->attachField('field_hero', 'supported_image');

    $fixture = dirname(__DIR__, 4) . '/fixtures/files/sample.jpg';

    $this->assertFieldRoundTripViaDriver('field_hero', [
      [
        'target_id' => $fixture,
        'alt' => 'Hero alt.',
        'title' => 'Hero title.',
      ],
    ]);
  }

}
