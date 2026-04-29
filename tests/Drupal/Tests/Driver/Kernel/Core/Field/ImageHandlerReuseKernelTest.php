<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Driver\Entity\EntityStub;
use Drupal\file\Entity\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for ImageHandler's existing-managed-file reuse path.
 *
 * Complements ImageHandlerKernelTest (upload path). Asserts the 2.x
 * contract: referencing a pre-created image by URI or bare basename
 * reuses its file id without uploading a new copy.
 *
 * @group fields
 */
#[Group('fields')]
class ImageHandlerReuseKernelTest extends FieldHandlerKernelTestBase {

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
   * Tests that referencing an image by URI reuses the same file id.
   */
  public function testReuseByFullUri(): void {
    $this->attachField('field_photo', 'image');

    $existing = $this->createManagedFileAt('public://existing-hero.jpg', 'fixture');

    $stub = new EntityStub(self::ENTITY_TYPE, self::BUNDLE, [
      'name' => 'reuse by uri',
      'field_photo' => ['public://existing-hero.jpg', 'alt' => 'Hero', 'title' => 'Hero title'],
    ]);

    $this->core->entityCreate($stub);

    $stored = $this->loadFirstItem($stub->getValue('id'), 'field_photo');
    $this->assertEquals($existing->id(), $stored->get('target_id')->getValue());
    $this->assertSame('Hero', $stored->get('alt')->getValue());
    $this->assertSame('Hero title', $stored->get('title')->getValue());
    $this->assertSame(1, $this->fileEntityCount());
  }

  /**
   * Tests that a bare basename resolves against public:// and reuses the id.
   */
  public function testReuseByBareBasename(): void {
    $this->attachField('field_photo', 'image');

    $existing = $this->createManagedFileAt('public://existing-logo.png', 'fixture');

    $stub = new EntityStub(self::ENTITY_TYPE, self::BUNDLE, [
      'name' => 'reuse by basename',
      'field_photo' => ['existing-logo.png'],
    ]);

    $this->core->entityCreate($stub);

    $stored = $this->loadFirstItem($stub->getValue('id'), 'field_photo');
    $this->assertEquals($existing->id(), $stored->get('target_id')->getValue());
    $this->assertSame(1, $this->fileEntityCount());
  }

  /**
   * Loads the first field-item of the named field on the given entity id.
   */
  private function loadFirstItem(int|string $entity_id, string $field_name): object {
    $entity = \Drupal::entityTypeManager()
      ->getStorage(self::ENTITY_TYPE)
      ->loadUnchanged($entity_id);
    $this->assertInstanceOf(ContentEntityInterface::class, $entity);

    return $entity->get($field_name)->first();
  }

  /**
   * Creates a managed File at the given URI with the given contents.
   */
  private function createManagedFileAt(string $uri, string $contents): File {
    file_put_contents($uri, $contents);

    $file = File::create([
      'uri' => $uri,
      'filename' => basename($uri),
      'status' => 1,
    ]);
    $file->save();

    return $file;
  }

  /**
   * Returns the total number of managed File entities currently in storage.
   */
  private function fileEntityCount(): int {
    return (int) \Drupal::entityTypeManager()
      ->getStorage('file')
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

}
