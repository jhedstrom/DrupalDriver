<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Driver\Core\Core;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Exception\CreationHintResolutionException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for node-related methods on Core via the driver.
 *
 * Exercises Core::nodeCreate and Core::nodeDelete end-to-end: bundle
 * validation, optional 'author' → 'uid' remapping, expandEntityFields
 * (no fields attached here, so it's a noop), save, and delete.
 *
 * @group core
 */
#[Group('core')]
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

    $stub = new EntityStub('node', 'article', [
      'title' => 'Hello world',
      'author' => 'article_author',
    ]);

    $result = $this->core->nodeCreate($stub);

    $this->assertSame($stub, $result, 'nodeCreate returns the same stub.');
    $this->assertNotEmpty($result->getValue('nid'), 'nodeCreate populated nid.');
    $this->assertTrue($result->isSaved(), 'nodeCreate marked the stub saved.');
    $node = Node::load($result->getValue('nid'));
    $this->assertInstanceOf(Node::class, $node);
    $this->assertSame('Hello world', $node->getTitle());
    $this->assertSame((int) $author->id(), (int) $node->getOwnerId(), 'author mapped to uid.');

    $this->core->nodeDelete($result);
    $this->assertNull(Node::load($result->getValue('nid')));
  }

  /**
   * Tests that nodeCreate rejects an unknown bundle.
   */
  public function testNodeCreateRejectsUnknownBundle(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cannot create content because provided content type bogus does not exist.');

    $this->core->nodeCreate(new EntityStub('node', 'bogus', ['title' => 'Nope']));
  }

  /**
   * Tests that nodeCreate rejects a node with no type.
   */
  public function testNodeCreateRejectsMissingType(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Cannot create content because it is missing the required property 'type'.");

    $this->core->nodeCreate(new EntityStub('node', NULL, ['title' => 'Nope']));
  }

  /**
   * Tests that nodeCreate rejects an unknown 'author' value.
   *
   * Previously a missing user was silently coerced into 'uid = 0', leaving
   * the typo invisible to the test author. The creation hint now throws.
   */
  public function testNodeCreateRejectsUnknownAuthor(): void {
    $this->expectException(CreationHintResolutionException::class);
    $this->expectExceptionMessageMatches("/user 'auther'.*does not exist/");

    $this->core->nodeCreate(new EntityStub('node', 'article', [
      'title' => 'Hello',
      'author' => 'auther',
    ]));
  }

}
