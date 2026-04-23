<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\Plugin\WorkflowType\ContentModeration;
use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\workflows\Entity\Workflow;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression test for F3 (moderation_state).
 *
 * When a stub carries a computed-writable base field such as
 * 'moderation_state', the driver must skip it entirely so the scalar flows
 * untouched into 'Node::create()' and the content_moderation save-hook
 * captures it. Before the F3 skip rule this case raised a RuntimeException
 * because the handler pipeline tried to marshal a field with no storage.
 *
 * @group core
 * @group fields
 */
#[Group('core')]
#[Group('fields')]
class CoreEntityCreateModerationStateKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'workflows',
    'content_moderation',
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
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('workflow');
    $this->installConfig(['system', 'filter', 'node']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    $workflow = Workflow::create([
      'id' => 'editorial',
      'label' => 'Editorial',
      'type' => 'content_moderation',
      'type_settings' => [
        'states' => [
          'draft' => ['label' => 'Draft', 'weight' => 0, 'published' => FALSE, 'default_revision' => FALSE],
          'published' => ['label' => 'Published', 'weight' => 1, 'published' => TRUE, 'default_revision' => TRUE],
        ],
        'transitions' => [
          'create_new_draft' => ['label' => 'Create draft', 'to' => 'draft', 'from' => ['draft'], 'weight' => 0],
          'publish' => ['label' => 'Publish', 'to' => 'published', 'from' => ['draft'], 'weight' => 1],
        ],
      ],
    ]);
    $type_plugin = $workflow->getTypePlugin();

    if (!$type_plugin instanceof ContentModeration) {
      throw new \LogicException('Expected a ContentModeration workflow type plugin.');
    }

    $type_plugin->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

    $this->core = new Core($this->root);
  }

  /**
   * Tests that 'moderation_state' on a stub is captured at save.
   */
  public function testEntityCreatePassesModerationStateThrough(): void {
    $stub = (object) [
      'type' => 'article',
      'title' => 'Draft article',
      'moderation_state' => 'draft',
    ];

    $this->core->entityCreate('node', $stub);

    $this->assertNotEmpty($stub->nid, 'entityCreate populated node nid on the stub.');

    $node = Node::load((int) $stub->nid);
    $this->assertInstanceOf(Node::class, $node);

    $revision_id = $node->getRevisionId();
    $moderation_state = ContentModerationState::loadFromModeratedEntity($node);

    $this->assertInstanceOf(
      ContentModerationState::class,
      $moderation_state,
      'content_moderation recorded a ContentModerationState for the node.',
    );
    $this->assertSame('draft', $moderation_state->get('moderation_state')->value);
    $this->assertSame((int) $revision_id, (int) $moderation_state->get('content_entity_revision_id')->value);
  }

}
