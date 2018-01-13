<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;
use Drupal\Tests\Driver\Kernel\Drupal8\Entity\DriverEntityKernelTestBase;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;

/**
 * Tests the driver's handling of comment entities.
 * They have the peculiarity of having 'entity_type' as a field name.
 *
 * @group Driver
 */
class CommentTest extends DriverEntityKernelTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['comment', 'user'];

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
  protected $entityType = 'comment';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['user', 'comment']);
    $this->installSchema('comment', ['comment_entity_statistics']);

    // Create a comment type.
    $comment_type = CommentType::create([
      'id' => 'testcomment',
      'label' => 'Default comments',
      'description' => 'Default comment field',
      'target_entity_type_id' => 'user',
    ]);
    $comment_type->save();

    // Add a comment field to the user entity.
    $this->addDefaultCommentField('user', 'user', 'comment', $default_value = CommentItemInterface::OPEN, $comment_type_id = 'testcomment');
  }

  /**
   * Test that a comment can be created and deleted.
   */
  public function testCommentCreateDelete() {
    // Create a comment on a test user.
    $user = $this->createUser();
    $subject = $this->randomString();
    $comment = (object) [
      'subject' => $subject,
      'entity_type' => 'user',
      'entity_id' => $user->getUsername(),
      'step_bundle' => 'testcomment'
    ];
    $comment = $this->driver->createEntity('comment', $comment);

    $entities = $this->storage->loadByProperties(['subject' => $subject]);
    $this->assertEquals(1, count($entities));

    // Check the id of the new comment has been added to the returned object.
    $entity = reset($entities);
    $this->assertEquals($entity->id(), $comment->id);

    // Check the comment can be deleted.
    $this->driver->entityDelete('comment', $comment);
    $entities = $this->storage->loadByProperties(['subject' => $subject]);
    $this->assertEquals(0, count($entities));

  }

  /**
   * Test that a comment can be created and deleted.
   */
  public function testCommentCreateDeleteByWrapper() {
    // Create a comment on a test user.
    $user = $this->createUser();
    $subject = $this->randomString();

    $fields = [
      'subject' => $subject,
      'entity_type' => 'user',
      'entity_id' => $user->getUsername(),
      'comment_type' => 'testcomment'
    ];
    $comment = DriverEntityDrupal8::create($fields, $this->entityType)->save();

    $entities = $this->storage->loadByProperties(['subject' => $subject]);
    $this->assertEquals(1, count($entities));

    // Check the id of the new comment has been added to the returned object.
    $entity = reset($entities);
    $this->assertEquals($entity->id(), $comment->id);

    // Check the comment can be deleted.
    $comment->delete();
    $entities = $this->storage->loadByProperties(['subject' => $subject]);
    $this->assertEquals(0, count($entities));



  }

}
