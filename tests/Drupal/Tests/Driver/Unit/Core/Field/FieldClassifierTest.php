<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\FieldClassifier;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the classifier against all nine F-row categories.
 *
 * @group core
 * @group fields
 */
#[Group('core')]
#[Group('fields')]
class FieldClassifierTest extends TestCase {

  /**
   * Tests F1 detection: standard-storage entity-type-wide base field.
   */
  public function testFieldIsBaseStandard(): void {
    $classifier = new FieldClassifier($this->entityFieldManager());

    $this->assertTrue($classifier->fieldIsBaseStandard('node', 'title'));
    $this->assertFalse($classifier->fieldIsBaseStandard('node', 'mod_readonly'));
    $this->assertFalse($classifier->fieldIsBaseStandard('node', 'mod_writable'));
    $this->assertFalse($classifier->fieldIsBaseStandard('node', 'base_custom'));
    $this->assertFalse($classifier->fieldIsBaseStandard('node', 'field_tags'));
    $this->assertFalse($classifier->fieldIsBaseStandard('node', 'nonexistent'));
  }

  /**
   * Tests F2 detection: computed read-only base field.
   */
  public function testFieldIsBaseComputedReadOnly(): void {
    $classifier = new FieldClassifier($this->entityFieldManager());

    $this->assertTrue($classifier->fieldIsBaseComputedReadOnly('node', 'mod_readonly'));
    $this->assertFalse($classifier->fieldIsBaseComputedReadOnly('node', 'title'));
    $this->assertFalse($classifier->fieldIsBaseComputedReadOnly('node', 'mod_writable'));
  }

  /**
   * Tests F3 detection: computed writable base field.
   */
  public function testFieldIsBaseComputedWritable(): void {
    $classifier = new FieldClassifier($this->entityFieldManager());

    $this->assertTrue($classifier->fieldIsBaseComputedWritable('node', 'mod_writable'));
    $this->assertFalse($classifier->fieldIsBaseComputedWritable('node', 'mod_readonly'));
    $this->assertFalse($classifier->fieldIsBaseComputedWritable('node', 'title'));
  }

  /**
   * Tests F4 detection: custom-storage base field.
   */
  public function testFieldIsBaseCustomStorage(): void {
    $classifier = new FieldClassifier($this->entityFieldManager());

    $this->assertTrue($classifier->fieldIsBaseCustomStorage('node', 'base_custom'));
    $this->assertFalse($classifier->fieldIsBaseCustomStorage('node', 'title'));
    $this->assertFalse($classifier->fieldIsBaseCustomStorage('node', 'mod_writable'));
  }

  /**
   * Tests F5 detection: FieldStorageConfig configurable field.
   */
  public function testFieldIsConfigurable(): void {
    $classifier = new FieldClassifier($this->entityFieldManager());

    $this->assertTrue($classifier->fieldIsConfigurable('node', 'field_tags'));
    $this->assertFalse($classifier->fieldIsConfigurable('node', 'title'));
    $this->assertFalse($classifier->fieldIsConfigurable('node', 'nonexistent'));
  }

  /**
   * Tests F6 detection: bundle-only computed read-only field.
   */
  public function testFieldIsBundleComputedReadOnly(): void {
    $classifier = new FieldClassifier($this->entityFieldManager());

    $this->assertTrue($classifier->fieldIsBundleComputedReadOnly('node', 'bundle_computed_ro', 'article'));
    $this->assertFalse($classifier->fieldIsBundleComputedReadOnly('node', 'bundle_computed_rw', 'article'));
    $this->assertFalse($classifier->fieldIsBundleComputedReadOnly('node', 'title', 'article'));
    $this->assertFalse($classifier->fieldIsBundleComputedReadOnly('node', 'nonexistent', 'article'));
  }

  /**
   * Tests F7 detection: bundle-only computed writable field.
   */
  public function testFieldIsBundleComputedWritable(): void {
    $classifier = new FieldClassifier($this->entityFieldManager());

    $this->assertTrue($classifier->fieldIsBundleComputedWritable('node', 'bundle_computed_rw', 'article'));
    $this->assertFalse($classifier->fieldIsBundleComputedWritable('node', 'bundle_computed_ro', 'article'));
    $this->assertFalse($classifier->fieldIsBundleComputedWritable('node', 'title', 'article'));
    $this->assertFalse($classifier->fieldIsBundleComputedWritable('node', 'nonexistent', 'article'));
  }

  /**
   * Tests F8 detection: bundle-only custom-storage field.
   */
  public function testFieldIsBundleCustomStorage(): void {
    $classifier = new FieldClassifier($this->entityFieldManager());

    $this->assertTrue($classifier->fieldIsBundleCustomStorage('node', 'bundle_custom', 'article'));
    $this->assertFalse($classifier->fieldIsBundleCustomStorage('node', 'bundle_computed_rw', 'article'));
    $this->assertFalse($classifier->fieldIsBundleCustomStorage('node', 'title', 'article'));
    $this->assertFalse($classifier->fieldIsBundleCustomStorage('node', 'nonexistent', 'article'));
  }

  /**
   * Tests F9 detection: storage-info hook + bundle-hook pair.
   */
  public function testFieldIsBundleStorageBacked(): void {
    $classifier = new FieldClassifier($this->entityFieldManager());

    $this->assertTrue($classifier->fieldIsBundleStorageBacked('node', 'bundle_storage_backed', 'article'));
    $this->assertFalse($classifier->fieldIsBundleStorageBacked('node', 'title', 'article'));
    $this->assertFalse($classifier->fieldIsBundleStorageBacked('node', 'field_tags', 'article'));
    $this->assertFalse($classifier->fieldIsBundleStorageBacked('node', 'nonexistent', 'article'));
    $this->assertFalse($classifier->fieldIsBundleStorageBacked('node', 'bundle_computed_rw', 'article'));
  }

  /**
   * Builds an entity-field-manager fixture with one field per F-row.
   */
  protected function entityFieldManager(): EntityFieldManagerInterface {
    // Storage stubs for the hasCustomStorage() chain on base definitions.
    $storage_no_custom = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage_no_custom->method('hasCustomStorage')->willReturn(FALSE);
    $storage_with_custom = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage_with_custom->method('hasCustomStorage')->willReturn(TRUE);

    // F1 standard base field.
    $title = $this->createMock(BaseFieldDefinition::class);
    $title->method('isComputed')->willReturn(FALSE);
    $title->method('getFieldStorageDefinition')->willReturn($storage_no_custom);
    $title->method('isReadOnly')->willReturn(FALSE);

    // F2 computed read-only base.
    $mod_ro = $this->createMock(BaseFieldDefinition::class);
    $mod_ro->method('isComputed')->willReturn(TRUE);
    $mod_ro->method('isReadOnly')->willReturn(TRUE);
    $mod_ro->method('getFieldStorageDefinition')->willReturn($storage_no_custom);

    // F3 computed writable base (moderation_state-like).
    $mod_rw = $this->createMock(BaseFieldDefinition::class);
    $mod_rw->method('isComputed')->willReturn(TRUE);
    $mod_rw->method('isReadOnly')->willReturn(FALSE);
    $mod_rw->method('getFieldStorageDefinition')->willReturn($storage_no_custom);

    // F4 custom-storage base.
    $base_custom = $this->createMock(BaseFieldDefinition::class);
    $base_custom->method('isComputed')->willReturn(FALSE);
    $base_custom->method('getFieldStorageDefinition')->willReturn($storage_with_custom);

    // F5 configurable field.
    $field_tags = $this->createMock(FieldStorageConfig::class);

    $base = [
      'title' => $title,
      'mod_readonly' => $mod_ro,
      'mod_writable' => $mod_rw,
      'base_custom' => $base_custom,
    ];

    $storage = [
      'title' => $title,
      'base_custom' => $base_custom,
      'field_tags' => $field_tags,
      // F9 storage entry (not a FieldStorageConfig, not in base).
      'bundle_storage_backed' => $this->createMock(FieldStorageDefinitionInterface::class),
    ];

    // F6 bundle-computed read-only.
    $bundle_ro = $this->createMock(FieldDefinitionInterface::class);
    $bundle_ro->method('isComputed')->willReturn(TRUE);
    $bundle_ro->method('isReadOnly')->willReturn(TRUE);

    // F7 bundle-computed writable.
    $bundle_rw = $this->createMock(FieldDefinitionInterface::class);
    $bundle_rw->method('isComputed')->willReturn(TRUE);
    $bundle_rw->method('isReadOnly')->willReturn(FALSE);

    // F8 bundle custom-storage.
    $bundle_custom_storage = $this->createMock(FieldStorageDefinitionInterface::class);
    $bundle_custom_storage->method('hasCustomStorage')->willReturn(TRUE);
    $bundle_custom = $this->createMock(FieldDefinitionInterface::class);
    $bundle_custom->method('isComputed')->willReturn(FALSE);
    $bundle_custom->method('getFieldStorageDefinition')->willReturn($bundle_custom_storage);

    // F9 definition in bundle (paired with the storage entry above).
    $bundle_f9 = $this->createMock(FieldDefinitionInterface::class);

    $bundle_defs = [
      'title' => $title,
      'field_tags' => $field_tags,
      'bundle_computed_ro' => $bundle_ro,
      'bundle_computed_rw' => $bundle_rw,
      'bundle_custom' => $bundle_custom,
      'bundle_storage_backed' => $bundle_f9,
    ];

    $manager = $this->createMock(EntityFieldManagerInterface::class);
    $manager->method('getBaseFieldDefinitions')->with('node')->willReturn($base);
    $manager->method('getFieldStorageDefinitions')->with('node')->willReturn($storage);
    $manager->method('getFieldDefinitions')->with('node', 'article')->willReturn($bundle_defs);

    return $manager;
  }

}
