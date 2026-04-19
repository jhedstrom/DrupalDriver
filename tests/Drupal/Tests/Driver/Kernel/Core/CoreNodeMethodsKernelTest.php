<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Kernel test for node-related methods on Core via the driver.
 *
 * Exercises Core::nodeCreate and Core::nodeDelete end-to-end: bundle
 * validation, optional 'author' → 'uid' remapping, expandEntityFields
 * (no fields attached here, so it's a noop), save, and delete.
 */
class CoreNodeMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
  ];

  /**
   * The Core driver under test.
   */
  protected Core $core;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'user', 'node', 'filter']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    $this->core = new Core($this->root);
  }

  /**
   * Tests the node lifecycle: create with author mapping, then delete.
   */
  public function testNodeLifecycle(): void {
    $author = User::create(['name' => 'article_author', 'status' => 1]);
    $author->save();

    $node_data = (object) [
      'type' => 'article',
      'title' => 'Hello world',
      'author' => 'article_author',
    ];

    $result = $this->core->nodeCreate($node_data);

    $this->assertNotEmpty($result->nid, 'nodeCreate populated nid.');
    $node = Node::load($result->nid);
    $this->assertInstanceOf(Node::class, $node);
    $this->assertSame('Hello world', $node->getTitle());
    $this->assertSame((int) $author->id(), (int) $node->getOwnerId(), 'author mapped to uid.');

    $this->core->nodeDelete($result);
    $this->assertNull(Node::load($result->nid));
  }

  /**
   * Tests that nodeCreate rejects an unknown bundle.
   */
  public function testNodeCreateRejectsUnknownBundle(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cannot create content because provided content type bogus does not exist.');

    $this->core->nodeCreate((object) ['type' => 'bogus', 'title' => 'Nope']);
  }

  /**
   * Tests that nodeCreate rejects a node with no type.
   */
  public function testNodeCreateRejectsMissingType(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Cannot create content because it is missing the required property 'type'.");

    $this->core->nodeCreate((object) ['title' => 'Nope']);
  }

}
