<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;

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
   * Test referencing a user by name (they don't have a label key or bundles,
   * so their driver entity plugin has to say what field to reference by).
   */
  public function testUserReferenceByName() {
    $this->fieldStorageSettings = ['target_type' => 'user'];
    $field = [$this->entities['user1']->name->value];
    $fieldExpected = [['target_id' => $this->entities['user1']->id()]];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test referencing a user by mail (they don't have a label key or bundles,
   * so their driver entity plugin has to say what field to reference by).
   */
  public function testUserReferenceByMail() {
    $this->fieldStorageSettings = ['target_type' => 'user'];
    $mail = $this->randomMachineName() . '@' . $this->randomMachineName() . '.com';
    $this->entities['user1']->set('mail', $mail)->save();
    $field = [$mail];
    $fieldExpected = [['target_id' => $this->entities['user1']->id()]];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test referencing a role by label.
   * Roles have string id's so can be referenced by label or id.
   */
  public function testRoleReferenceByLabel() {
    $this->installEntitySchema('user_role');
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role label'])->save();
    $this->fieldStorageSettings = ['target_type' => 'user_role'];
    $field = ['Test role label'];
    $fieldExpected = [['target_id' => 'test_role']];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test referencing a role by id.
   * Roles have string id's so can be referenced by label or id.
   */
  public function testRoleReferenceById() {
    $this->installEntitySchema('user_role');
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role label'])->save();
    $this->fieldStorageSettings = ['target_type' => 'user_role'];
    $field = ['test_role'];
    $fieldExpected = [['target_id' => 'test_role']];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test referencing a role by id without underscores.
   * Roles have string id's so can be referenced by label or id.
   */
  public function testRoleReferenceByIdWithoutUnderscores() {
    $this->installEntitySchema('user_role');
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role label'])->save();
    $this->fieldStorageSettings = ['target_type' => 'user_role'];
    $field = ['test role'];
    $fieldExpected = [['target_id' => 'test_role']];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test referencing a role by id case insensitively.
   * Roles have string id's so can be referenced by label or id.
   */

  /*
   * It would be good to have case insensitive references, but it seems that
     config entityqueries are intrinsically case sensitive. Test commented out
     until issue is resolved.

  public function testRoleReferenceCaseInsensitive() {
    $this->installEntitySchema('user_role');
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role label'])->save();
    $this->fieldStorageSettings = ['target_type' => 'user_role'];
    $field = ['TEST_role'];
    $fieldExpected = [['target_id' => 'test_role']];
    $this->assertCreatedWithField($field, $fieldExpected);
  }
  */

}
