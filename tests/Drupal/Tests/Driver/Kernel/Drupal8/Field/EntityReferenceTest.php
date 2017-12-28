<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Tests the driver's handling of entity reference fields.
 *
 * @group driver
 */
class EntityReferenceTest extends DriverFieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'field', 'user', 'node'];

  /**
   * Machine name of the field type being tested.
   *
   * @string
   */
  protected $fieldType = 'entity_reference';

  /**
   * Entities available to reference.
   *
   * @array
   */
  protected $entities = [];

  protected function setUp() {
    parent::setUp();
    $nodeType = NodeType::create(['type' => 'article', 'name' => 'article'])
      ->save();
    $this->entities['node1'] = Node::Create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
    ]);
    $this->entities['node1']->save();
    $this->entities['node2'] = Node::Create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
    ]);
    $this->entities['node2']->save();
    $this->entities['node3'] = Node::Create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
    ]);
    $this->entities['node3']->save();
    $this->entities['user1'] = User::Create(['name' => $this->randomMachineName()]);
    $this->entities['user1']->save();
  }

  /**
   * Test referencing a node using its title.
   */
  public function testNodeReferenceSingle() {
    $this->fieldStorageSettings = ['target_type' => 'node'];
    $this->fieldSettings = [
      'handler' => 'default',
      'handler_settings' => ['target_bundles' => ['article']],
      ];
    $field = [$this->entities['node1']->label()];
    $fieldExpected = [['target_id' => $this->entities['node1']->id()]];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test referencing multiple nodes using their title.
   */
  public function testNodeReferenceMultiple() {
    $this->fieldStorageSettings = ['target_type' => 'node'];
    $this->fieldSettings = [
      'handler' => 'default',
      'handler_settings' => ['target_bundles' => ['article']],
    ];
    $field = [
      $this->entities['node3']->label(),
      $this->entities['node1']->label(),
      $this->entities['node2']->label(),
    ];
    $fieldExpected = [
      ['target_id' => $this->entities['node3']->id()],
      ['target_id' => $this->entities['node1']->id()],
      ['target_id' => $this->entities['node2']->id()],
    ];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test referencing a user (they don't have a label key or bundles).
   */
  public function testUserReference() {
    $this->fieldStorageSettings = ['target_type' => 'user'];
    $field = [$this->entities['user1']->name->value];
    $fieldExpected = [['target_id' => $this->entities['user1']->id()]];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

}
