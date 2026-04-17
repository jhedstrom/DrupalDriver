<?php

declare(strict_types=1);

// Define a stub FieldStorageConfig in the correct namespace so that
// instanceof checks in Drupal8::isField() work without a full Drupal bootstrap.
// This must be declared before any class that references it.
// phpcs:disable
namespace Drupal\field\Entity {
  if (!class_exists(\Drupal\field\Entity\FieldStorageConfig::class)) {
    class FieldStorageConfig {
      public function __construct(protected string $type)
      {
      }
      public function getType(): string { return $this->type; }
    }
  }
}
// phpcs:enable

namespace Drupal\Tests\Driver {

  use Drupal\Core\Entity\EntityFieldManagerInterface;
  use Drupal\Core\Field\BaseFieldDefinition;
  use Drupal\Driver\Cores\Drupal8;
  use Drupal\field\Entity\FieldStorageConfig;
  use PHPUnit\Framework\TestCase;

  /**
   * Tests 'isBaseField()', 'isField()', and 'getEntityFieldTypes()' methods.
   */
  class Drupal8FieldMethodsTest extends TestCase {

    /**
     * Tests that 'isBaseField()' correctly identifies base fields.
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
      $this->assertSame($expected, $core->isBaseField('node', $field_name));
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
     * Tests that 'isField()' correctly identifies configurable fields.
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
      $this->assertSame($expected, $core->isField('node', $field_name));
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
     * Creates a TestDrupal8Core with mocked entity field manager.
     *
     * @return \Drupal\Tests\Driver\TestDrupal8Core
     *   The test core instance.
     */
    protected function createTestCore(): TestDrupal8Core {
      // Non-computed base field.
      $title_field = $this->createMock(BaseFieldDefinition::class);
      $title_field->method('getType')->willReturn('string');

      // Computed base field (not in getFieldStorageDefinitions).
      $moderation_state_field = $this->createMock(BaseFieldDefinition::class);
      $moderation_state_field->method('getType')->willReturn('string');

      // Configurable field - stub that passes instanceof FieldStorageConfig.
      $field_tags = new FieldStorageConfig('entity_reference');

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

      $core = new TestDrupal8Core(__DIR__, 'default');
      $core->setEntityFieldManager($entity_field_manager);
      return $core;
    }

  }

  /**
   * Testable subclass that overrides 'getEntityFieldManager()'.
   */
  class TestDrupal8Core extends Drupal8 {

    /**
     * The mock entity field manager.
     */
    protected EntityFieldManagerInterface $entityFieldManager;

    /**
     * Sets the mock entity field manager.
     *
     * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
     *   The mock entity field manager.
     */
    public function setEntityFieldManager(EntityFieldManagerInterface $entity_field_manager): void {
      $this->entityFieldManager = $entity_field_manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityFieldManager(): object {
      return $this->entityFieldManager;
    }

  }

}
