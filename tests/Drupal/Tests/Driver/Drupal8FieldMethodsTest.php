<?php

// Define a stub FieldStorageConfig in the correct namespace so that
// instanceof checks in Drupal8::isField() work without a full Drupal bootstrap.
// This must be declared before any class that references it.
// phpcs:disable
namespace Drupal\field\Entity {
  if (!class_exists(\Drupal\field\Entity\FieldStorageConfig::class)) {
    class FieldStorageConfig {
      protected $type;
      public function __construct(string $type) { $this->type = $type; }
      public function getType() { return $this->type; }
    }
  }
}
// phpcs:enable

namespace Drupal\Tests\Driver {

  use Drupal\Core\Entity\EntityFieldManagerInterface;
  use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
  use Drupal\Core\Field\BaseFieldDefinition;
  use Drupal\Core\Field\FieldDefinitionInterface;
  use Drupal\Driver\Cores\Drupal8;
  use Drupal\field\Entity\FieldStorageConfig;
  use PHPUnit\Framework\TestCase;
  use Symfony\Component\DependencyInjection\ContainerInterface;

  /**
   * Tests for Drupal8 field methods: isBaseField(), isField(), getEntityFieldTypes().
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
    public function testIsBaseField($field_name, $expected) {
      $core = $this->createTestCore();
      $this->assertSame($expected, $core->isBaseField('node', $field_name));
    }

    /**
     * Data provider for testIsBaseField().
     */
    public static function dataProviderIsBaseField() {
      return [
        'non-computed base field' => ['title', TRUE],
        'computed base field' => ['moderation_state', TRUE],
        'configurable field' => ['field_tags', FALSE],
        'unknown field' => ['nonexistent', FALSE],
        'bundle computed field' => ['uri', TRUE],
        'unknown bundle field' => ['nonexistent_bundle_field', FALSE],
      ];
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
    public function testIsField($field_name, $expected) {
      $core = $this->createTestCore();
      $this->assertSame($expected, $core->isField('node', $field_name));
    }

    /**
     * Data provider for testIsField().
     */
    public static function dataProviderIsField() {
      return [
        'configurable field' => ['field_tags', TRUE],
        'non-computed base field' => ['title', FALSE],
        'computed base field' => ['moderation_state', FALSE],
        'unknown field' => ['nonexistent', FALSE],
      ];
    }

    /**
     * Tests that 'getEntityFieldTypes()' includes computed base fields.
     *
     * @param array $base_fields_arg
     *   The $base_fields argument to pass.
     * @param array $expected_fields
     *   The expected field names in the result.
     * @param array $unexpected_fields
     *   Field names that should NOT be in the result.
     *
     * @dataProvider dataProviderGetEntityFieldTypes
     */
    public function testGetEntityFieldTypes(array $base_fields_arg, array $expected_fields, array $unexpected_fields) {
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
    public static function dataProviderGetEntityFieldTypes() {
      return [
        'no base fields requested' => [
        [],
        ['field_tags'],
        ['title', 'moderation_state'],
        ],
        'non-computed base field requested' => [
        ['title'],
        ['field_tags', 'title'],
        ['moderation_state'],
        ],
        'computed base field requested' => [
        ['moderation_state'],
        ['field_tags', 'moderation_state'],
        ['title'],
        ],
        'multiple base fields requested' => [
        ['title', 'moderation_state'],
        ['field_tags', 'title', 'moderation_state'],
        [],
        ],
      ];
    }

    /**
     * Resets the Drupal container after each test.
     */
    protected function tearDown(): void {
      parent::tearDown();
      \Drupal::unsetContainer();
    }

    /**
     * Creates a TestDrupal8Core with mocked entity field manager and container.
     *
     * @return \Drupal\Tests\Driver\TestDrupal8Core
     *   The test core instance.
     */
    protected function createTestCore() {
      // Non-computed base field.
      $title_field = $this->createMock(BaseFieldDefinition::class);
      $title_field->method('getType')->willReturn('string');

      // Computed base field (not in getFieldStorageDefinitions).
      $moderation_state_field = $this->createMock(BaseFieldDefinition::class);
      $moderation_state_field->method('getType')->willReturn('string');

      // Configurable field — use stub that passes instanceof FieldStorageConfig.
      $field_tags = new FieldStorageConfig('entity_reference');

      // Bundle computed field: present in a specific bundle, not in base fields
      // or field storage definitions (no FieldStorageConfig).
      $uri_field = $this->createMock(FieldDefinitionInterface::class);
      $uri_field->method('getType')->willReturn('uri');

      $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);

      // getFieldStorageDefinitions: non-computed base fields + configurable fields.
      $entity_field_manager->method('getFieldStorageDefinitions')
        ->with('node')
        ->willReturn([
          'title' => $title_field,
          'field_tags' => $field_tags,
        ]);

      // getBaseFieldDefinitions: ALL base fields (computed + non-computed).
      $entity_field_manager->method('getBaseFieldDefinitions')
        ->with('node')
        ->willReturn([
          'title' => $title_field,
          'moderation_state' => $moderation_state_field,
        ]);

      // getFieldDefinitions: bundle-specific fields per bundle. 'uri' exists only
      // on 'solution'; 'field_tags' appears on 'article' as a configurable field.
      $entity_field_manager->method('getFieldDefinitions')
        ->willReturnMap([
          ['node', 'article', ['title' => $title_field, 'field_tags' => $field_tags]],
          ['node', 'solution', ['title' => $title_field, 'uri' => $uri_field]],
        ]);

      // Set up the Drupal container so isBaseField() can resolve entity_type.bundle.info.
      $bundle_info_service = $this->createMock(EntityTypeBundleInfoInterface::class);
      $bundle_info_service->method('getBundleInfo')
        ->with('node')
        ->willReturn([
          'article' => ['label' => 'Article'],
          'solution' => ['label' => 'Solution'],
        ]);

      $container = $this->createMock(ContainerInterface::class);
      $container->method('get')
        ->with('entity_type.bundle.info')
        ->willReturn($bundle_info_service);
      \Drupal::setContainer($container);

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
     *
     * @var \Drupal\Core\Entity\EntityFieldManagerInterface
     */
    protected $entityFieldManager;

    /**
     * Sets the mock entity field manager.
     *
     * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
     *   The mock entity field manager.
     */
    public function setEntityFieldManager(EntityFieldManagerInterface $entity_field_manager) {
      $this->entityFieldManager = $entity_field_manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityFieldManager() {
      return $this->entityFieldManager;
    }

  }

}
