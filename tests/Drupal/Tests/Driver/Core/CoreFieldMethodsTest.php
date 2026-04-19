<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Core;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Driver\Core\Core;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests 'fieldIsBase()', 'fieldExists()', and 'getEntityFieldTypes()' methods.
 */
class CoreFieldMethodsTest extends TestCase {

  /**
   * Tests that 'fieldIsBase()' correctly identifies base fields.
   *
   * @param string $field_name
   *   The field name to check.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider dataProviderIsBaseField
   */
  public function testIsBaseField(string $field_name, bool $expected): void {
    $core = $this->createTestCore();
    $this->assertSame($expected, $core->fieldIsBase('node', $field_name));
  }

  /**
   * Data provider for testIsBaseField().
   */
  public static function dataProviderIsBaseField(): \Iterator {
    yield 'non-computed base field' => ['title', TRUE];
    yield 'computed base field' => ['moderation_state', TRUE];
    yield 'configurable field' => ['field_tags', FALSE];
    yield 'unknown field' => ['nonexistent', FALSE];
  }

  /**
   * Tests that 'fieldExists()' correctly identifies configurable fields.
   *
   * @param string $field_name
   *   The field name to check.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider dataProviderIsField
   */
  public function testIsField(string $field_name, bool $expected): void {
    $core = $this->createTestCore();
    $this->assertSame($expected, $core->fieldExists('node', $field_name));
  }

  /**
   * Data provider for testIsField().
   */
  public static function dataProviderIsField(): \Iterator {
    yield 'configurable field' => ['field_tags', TRUE];
    yield 'non-computed base field' => ['title', FALSE];
    yield 'computed base field' => ['moderation_state', FALSE];
    yield 'unknown field' => ['nonexistent', FALSE];
  }

  /**
   * Tests that 'getEntityFieldTypes()' includes computed base fields.
   *
   * @param array<string> $base_fields_arg
   *   The $base_fields argument to pass.
   * @param array<string> $expected_fields
   *   The expected field names in the result.
   * @param array<string> $unexpected_fields
   *   Field names that should NOT be in the result.
   *
   * @dataProvider dataProviderGetEntityFieldTypes
   */
  public function testGetEntityFieldTypes(array $base_fields_arg, array $expected_fields, array $unexpected_fields): void {
    $core = $this->createTestCore();
    $result = $core->getEntityFieldTypes('node', $base_fields_arg);

    foreach ($expected_fields as $field_name) {
      $this->assertArrayHasKey($field_name, $result, sprintf("Expected '%s' in result.", $field_name));
    }
    foreach ($unexpected_fields as $field_name) {
      $this->assertArrayNotHasKey($field_name, $result, sprintf("Did not expect '%s' in result.", $field_name));
    }
  }

  /**
   * Data provider for testGetEntityFieldTypes().
   */
  public static function dataProviderGetEntityFieldTypes(): \Iterator {
    yield 'no base fields requested' => [
      [],
      ['field_tags'],
      ['title', 'moderation_state'],
    ];
    yield 'non-computed base field requested' => [
      ['title'],
      ['field_tags', 'title'],
      ['moderation_state'],
    ];
    yield 'computed base field requested' => [
      ['moderation_state'],
      ['field_tags', 'moderation_state'],
      ['title'],
    ];
    yield 'multiple base fields requested' => [
      ['title', 'moderation_state'],
      ['field_tags', 'title', 'moderation_state'],
      [],
    ];
  }

  /**
   * Creates a TestCore with a mocked entity field manager.
   */
  protected function createTestCore(): TestCore {
    // Non-computed base field.
    $title_field = $this->createMock(BaseFieldDefinition::class);
    $title_field->method('getType')->willReturn('string');

    // Computed base field (not in getFieldStorageDefinitions).
    $moderation_state_field = $this->createMock(BaseFieldDefinition::class);
    $moderation_state_field->method('getType')->willReturn('string');

    // Configurable field - mock satisfies `instanceof FieldStorageConfig` in
    // Core::fieldExists without needing the real constructor's array arg.
    $field_tags = $this->createMock(FieldStorageConfig::class);
    $field_tags->method('getType')->willReturn('entity_reference');

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);

    // getFieldStorageDefinitions: returns non-computed base fields and
    // configurable fields.
    $entity_field_manager->method('getFieldStorageDefinitions')
      ->with('node')
      ->willReturn([
        'title' => $title_field,
        'field_tags' => $field_tags,
      ]);

    // getBaseFieldDefinitions: returns ALL base fields (computed and
    // non-computed).
    $entity_field_manager->method('getBaseFieldDefinitions')
      ->with('node')
      ->willReturn([
        'title' => $title_field,
        'moderation_state' => $moderation_state_field,
      ]);

    $core = new TestCore(__DIR__, 'default');
    $core->setEntityFieldManager($entity_field_manager);
    return $core;
  }

}

/**
 * Testable subclass that overrides 'getEntityFieldManager()'.
 */
class TestCore extends Core {

  /**
   * The mock entity field manager.
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * Sets the mock entity field manager.
   */
  public function setEntityFieldManager(EntityFieldManagerInterface $entity_field_manager): void {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityFieldManager(): EntityFieldManagerInterface {
    return $this->entityFieldManager;
  }

}
