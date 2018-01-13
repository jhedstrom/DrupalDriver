<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\file\Entity\File;

/**
 * Tests the driver's handling of image fields.
 *
 * @group driver
 */
class ImageTest extends DriverFieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'field', 'image', 'file'];

  /**
   * Machine name of the field type being tested.
   *
   * @string
   */
  protected $fieldType = 'image';

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * Test referencing an image by a uri.
   */
  public function testImageFromUri() {
    $fieldIntended = [
      'http://www.google.com',
      ];
    $entity = $this->createTestEntity($fieldIntended);
    $this->assertValidField($entity);
    $field = $entity->get($this->fieldName);
    $fileId = $field->getValue()[0]['target_id'];
    $file = File::load($fileId);
    $this->assertFileExists($file->getFileUri());
  }


  /**
   * Test referencing multiple images by uri.
   */
  public function testMultipleImagesFromUri() {
    $fieldIntended = [
      'http://www.google.com',
      'http://www.drupal.com',
    ];
    $entity = $this->createTestEntity($fieldIntended);
    $this->assertValidField($entity);
    $field = $entity->get($this->fieldName);
    $fileId1 = $field->getValue()[0]['target_id'];
    $fileId2 = $field->getValue()[1]['target_id'];
    $file1 = File::load($fileId1);
    $this->assertFileExists($file1->getFileUri());
    $file2 = File::load($fileId2);
    $this->assertFileExists($file2->getFileUri());
  }

}
