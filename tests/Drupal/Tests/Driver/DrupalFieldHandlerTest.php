<?php

namespace Drupal\Tests\Driver;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Drupal >= 8 field handlers.
 */
class DrupalFieldHandlerTest extends TestCase {

  /**
   * Tests the entity reference field handlers.
   *
   * @param string $class
   *   The name of the field handler class under test.
   * @param array $entity
   *   An associative array representing an entity. Should contain a single
   *   item which represents a field containing a value. It will be converted to
   *   a \stdClass object before passed to the handler constructor.
   * @param string $entity_type_id
   *   The host entity type ID.
   * @param array $field_settings
   *   An array containing the field settings having the following keys:
   *   - field_type: The field type plugin ID,
   *   - main_property: The result of FieldItemInterface::mainPropertyName(),
   *   - field_name: The name of the field,
   *   - target_type: The target entity type ID,
   *   - handler_settings: The entity reference field handler settings.
   * @param array $expected_values
   *   The values in the expected format after expansion.
   *
   * @dataProvider dataProviderEntityReferenceHandler
   */
  public function testEntityReferenceHandlers(string $class, array $entity, string $entity_type_id, array $field_settings, array $expected_values): void {
    $handler = \Mockery::mock("Drupal\\Driver\\Fields\\Drupal8\\$class");
    $handler->makePartial()->shouldAllowMockingProtectedMethods();
    $handler->shouldReceive('getEntityFieldManager')->andReturn($this->getEntityFieldManagerMock($entity_type_id, $field_settings));
    $target_entity_type_id = $field_settings['target_type'];
    $target_entity_data = $this->getTargetEntityData($target_entity_type_id);
    $handler->shouldReceive('getEntityTypeKey')->andReturnUsing(function (string $entity_type_id, string $key) use ($target_entity_data) {
      return $target_entity_data['keys'][$key];
    });
    $handler->shouldReceive('getTargetBundles')->andReturnUsing(function () use ($field_settings): ?array {
      return $settings['handler_settings']['target_bundles'] ?? NULL;
    });
    $handler->shouldReceive('getEntityReferenceIdFromLabel')->andReturnUsing(function (string $label) use ($target_entity_data) {
      return $target_entity_data['samples'][$label];
    });
    $handler->__construct((object) $entity, $entity_type_id, $field_settings['field_name']);

    $expanded_values = $handler->expand($entity[$field_settings['field_name']]);
    Assert::assertSame($expected_values, $expanded_values);
  }

  /**
   * Data provider.
   *
   * @return array[][]
   *   An array of test data.
   *
   * @see testEntityReferenceHandlers
   */
  public function dataProviderEntityReferenceHandler(): array {
    return [
      'simple target file, no property key' => [
        'FileHandler',
        ['field_file' => 'foo.txt'],
        'node',
        [
          'field_type' => 'file',
          'main_property' => 'target_id',
          'field_name' => 'field_file',
          'target_type' => 'file',
          'handler_settings' => [],
        ],
        [1],
      ],
      'muliple target files, no property keys' => [
        'FileHandler',
        [
          'field_file' => [
            'foo.txt',
            'bar.png',
          ],
        ],
        'node',
        [
          'field_type' => 'file',
          'main_property' => 'target_id',
          'field_name' => 'field_file',
          'target_type' => 'file',
          'handler_settings' => [],
        ],
        [1, 5],
      ],
      'muliple target files, some property keys' => [
        'FileHandler',
        [
          'field_file' => [
            'foo.txt',
            'bar.png',
            ['target_id' => 'baz.jpg'],
          ],
        ],
        'node',
        [
          'field_type' => 'file',
          'field_name' => 'field_file',
          'main_property' => 'target_id',
          'target_type' => 'file',
          'handler_settings' => [],
        ],
        [1, 5, ['target_id' => 99]],
      ],
      'muliple target files, some with non-main properties' => [
        'FileHandler',
        [
          'field_file' => [
            'foo.txt',
            [
              'target_id' => 'bar.png',
              'display' => 1,
              'description' => 'The Logo',
            ],
            [
              'target_id' => 'baz.jpg',
              'display' => 0,
            ],
          ],
        ],
        'node',
        [
          'field_type' => 'file',
          'main_property' => 'target_id',
          'field_name' => 'field_file',
          'target_type' => 'file',
          'handler_settings' => [],
        ],
        [
          1,
          [
            'target_id' => 5,
            'display' => 1,
            'description' => 'The Logo',
          ],
          [
            'target_id' => 99,
            'display' => 0,
          ],
        ],
      ],
    ];
  }

  /**
   * Mocks the entity field manager service.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface|\Mockery\LegacyMockInterface|\Mockery\MockInterface
   *   Entity field manager service mock.
   */
  protected function getEntityFieldManagerMock(string $entity_type_id, array $field_settings) {
    $field_storage_config = \Mockery::mock(FieldStorageConfig::class, [
      'field_name' => $field_settings['field_name'],
      'entity_type' => $entity_type_id,
      'type' => $field_settings['field_type'],
    ])->makePartial();
    $field_storage_config->shouldReceive('getSetting')->with('target_type')->andReturn($field_settings['target_type']);
    $field_storage_config->shouldReceive('getMainPropertyName')->andReturn($field_settings['main_property']);

    $entity_field_manager = \Mockery::mock(EntityFieldManagerInterface::class);
    $entity_field_manager->shouldReceive('getFieldStorageDefinitions')->andReturn([
      $field_settings['field_name'] => $field_storage_config,
    ]);
    $entity_field_manager->shouldReceive('getFieldDefinitions')->andReturn([
      $field_settings['field_name'] => new FieldConfig([
        'field_name' => $field_settings['field_name'],
        'entity_type' => $entity_type_id,
        'bundle' => (new Random())->name(),
        'field_type' => $field_settings['field_type'],
      ]),
    ]);
    return $entity_field_manager;
  }

  /**
   * Returns target entity data used either in mocks or in test.
   *
   * @return array
   *   Associative array of target entity data keyed by target entity type ID
   *   and having target entity data as values.
   */
  protected function getTargetEntityData(string $target_entity_type_id): array {
    $target_entity_data = [
      'node' => [
        'keys' => [
          'label' => 'title',
          'bundle' => 'type',
        ],
      ],
      'file' => [
        'keys' => [
          'label' => 'filename',
          'bundle' => FALSE,
        ],
        'samples' =>  [
          'foo.txt' => 1,
          'bar.png' => 5,
          'baz.jpg' => 99,
        ],
      ],
    ];
    return $target_entity_data[$target_entity_type_id];
  }

}
