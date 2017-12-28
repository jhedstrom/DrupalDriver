<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\Tests\Driver\Kernel\Drupal8\Entity\DriverEntityKernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the driver's handling of node entities.
 *
 * @group driver
 */
class NodeTest extends DriverEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node',];

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
  protected $entityType = 'node';

  protected function setUp() {
    parent::setUp();
    $type = NodeType::create(['type' => 'article', 'name' => 'article']);
    $type->save();

    // Add a body field to articles.
    $this->installConfig('node');
    node_add_body_field($type);

    // Without node_access an error is thrown on deletion.
    $this->installSchema('node', 'node_access');
  }


  /**
   * Test that a node can be created and deleted.
   */
  public function testNodeCreateDelete() {
    $title = $this->driver->getRandom()->string();
    $node = (object) [
      'title' => $title,
      'type' => 'article',
    ];
    $node = $this->driver->createNode($node);

    $entities = $this->storage->loadByProperties(['title' => $title]);
    $this->assertEquals(1, count($entities));

    // Check the id of the new node has been added to the returned object.
    $entity = reset($entities);
    $this->assertObjectHasAttribute('nid', $node);
    $this->assertEquals($entity->id(), $node->nid);

    // Check the node can be deleted.
    $this->driver->nodeDelete($node);
    $entities = $this->storage->loadByProperties(['title' => $title]);
    $this->assertEquals(0, count($entities));
  }

  /**
   * Test that a node can be created specifying its author by name.
   */
  public function testNodeCreateWithAuthorName() {
    $title = $this->randomString();
    $author = $this->createUser();
    $authorName = $author->getUsername();
    $node = (object) [
      'title' => $title,
      'type' => 'article',
      'author' => $authorName,
    ];
    $node = $this->driver->createNode($node);

    $entities = $this->storage->loadByProperties(['title' => $title]);
    $this->assertEquals(1, count($entities));
    $entity = reset($entities);
    $this->assertEquals($author->id(), $entity->getOwnerId());
  }

  /**
   * Test that a node can be created specifying its body field.
   */
  public function testNodeCreateWithBody() {
    $title = $this->randomString();
    $body = $this->randomString();
    $node = (object) [
      'title' => $title,
      'type' => 'article',
      'body' => $body,
    ];
    $node = $this->driver->createNode($node);

    $entities = $this->storage->loadByProperties(['title' => $title]);
    $this->assertEquals(1, count($entities));
    $entity = reset($entities);
    $this->assertEquals($body, $entity->get('body')->value);
  }

}
