<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Core;
use Drupal\Driver\Core\Field\FieldClassifier;
use Drupal\Driver\Core\Field\FieldClassifierInterface;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests 'getEntityFieldTypes()' against the classifier-backed predicates.
 *
 * @group core
 * @group fields
 */
#[Group('core')]
#[Group('fields')]
class CoreFieldMethodsTest extends TestCase {

  /**
   * Tests that 'getEntityFieldTypes()' returns configurable and F1 base fields.
   */
  public function testGetEntityFieldTypesIncludesF1AndF5AndExcludesF3(): void {
    $core = $this->createTestCore();
    $result = $core->getEntityFieldTypes('node');

    $this->assertArrayHasKey('title', $result, 'F1 standard base field included.');
    $this->assertArrayHasKey('field_tags', $result, 'F5 configurable field included.');
    $this->assertArrayNotHasKey('moderation_state', $result, 'F3 computed writable base field excluded.');
  }

  /**
   * Creates a TestCore wired to a mocked entity field manager and classifier.
   */
  protected function createTestCore(): TestCore {
    $storage_no_custom = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage_no_custom->method('hasCustomStorage')->willReturn(FALSE);

    $title_field = $this->createMock(BaseFieldDefinition::class);
    $title_field->method('getType')->willReturn('string');
    $title_field->method('isComputed')->willReturn(FALSE);
    $title_field->method('getFieldStorageDefinition')->willReturn($storage_no_custom);

    $moderation_state_field = $this->createMock(BaseFieldDefinition::class);
    $moderation_state_field->method('getType')->willReturn('string');
    $moderation_state_field->method('isComputed')->willReturn(TRUE);
    $moderation_state_field->method('isReadOnly')->willReturn(FALSE);
    $moderation_state_field->method('getFieldStorageDefinition')->willReturn($storage_no_custom);

    $field_tags = $this->createMock(FieldStorageConfig::class);
    $field_tags->method('getType')->willReturn('entity_reference');

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_field_manager->method('getFieldStorageDefinitions')->with('node')->willReturn([
      'title' => $title_field,
      'field_tags' => $field_tags,
    ]);
    $entity_field_manager->method('getBaseFieldDefinitions')->with('node')->willReturn([
      'title' => $title_field,
      'moderation_state' => $moderation_state_field,
    ]);

    $core = new TestCore(__DIR__, 'default');
    $core->setEntityFieldManager($entity_field_manager);
    $core->setFieldClassifier(new FieldClassifier($entity_field_manager));

    return $core;
  }

}

/**
 * Testable subclass that injects a mocked entity field manager and classifier.
 */
class TestCore extends Core {

  /**
   * The injected mock entity field manager.
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * Sets the mock entity field manager.
   */
  public function setEntityFieldManager(EntityFieldManagerInterface $entity_field_manager): void {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Injects a pre-built classifier so the lazy factory is not consulted.
   */
  public function setFieldClassifier(FieldClassifierInterface $classifier): void {
    $this->fieldClassifier = $classifier;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityFieldManager(): EntityFieldManagerInterface {
    return $this->entityFieldManager;
  }

}
