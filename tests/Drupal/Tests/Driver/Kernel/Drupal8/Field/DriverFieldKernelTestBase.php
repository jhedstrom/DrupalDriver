<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;
use Drupal\Tests\Driver\Kernel\DriverKernelTestTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Base class for all Driver field kernel tests.
 */
class DriverFieldKernelTestBase extends EntityKernelTestBase
{

    use DriverKernelTestTrait;

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
    protected $entityType = 'entity_test';

  /**
   * Machine name of the field type being tested.
   *
   * @string
   */
    protected $fieldType;

  /**
   * Machine name of the field being tested.
   *
   * @string
   */
    protected $fieldName;

  /**
   * Bag of created field storages and fields.
   *
   * @var \ArrayObject
   */
    protected $fieldTestData;

  /**
   * Settings for the test field definition.
   *
   * @array
   */
    protected $fieldSettings;

  /**
   * Settings for the test field storage.
   *
   * @array
   */
    protected $fieldStorageSettings;

  /**
   * Entity storage.
   *
   * * @var \Drupal\Core\Entity\EntityStorageInterface;
   */
    protected $storage;

  /**
   * @inheritdoc
   */
    protected function setUp()
    {
        parent::setUp();
        $this->setUpDriver();
        $this->fieldTestData = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
        $this->storage = \Drupal::entityTypeManager()->getStorage($this->entityType);
        $this->fieldName = null;
        $this->fieldSettings = [];
        $this->fieldStorageSettings = [];
    }

  /**
   * Create a field and an associated field storage.
   *
   * @param string $field_type
   *   Machine name of the field type.
   * @param integer $cardinality
   *   (optional) Cardinality of the field.
   * @param array $field_settings
   *   (optional) Field settings.
   * @param array $field_storage_settings
   *   (optional) Field storage settings.
   * @param string $suffix
   *   (optional) A string that should only contain characters that are valid in
   *   PHP variable names as well.
   * @param string $entity_type
   *   (optional) The entity type on which the field should be created.
   *   Defaults to "entity_test".
   * @param string $bundle
   *   (optional) The entity type on which the field should be created.
   *   Defaults to the default bundle of the entity type.
   */
    protected function createFieldForDriverTest($field_type, $cardinality = 1, $field_settings = [], $field_storage_settings = [], $suffix = '', $entity_type = 'entity_test', $bundle = null, $field_name_prefix = null)
    {
        if (empty($bundle)) {
            $bundle = $entity_type;
        }
        $field_name = 'field_name' . $suffix;
        $field_storage = 'field_storage' . $suffix;
        $field_storage_uuid = 'field_storage_uuid' . $suffix;
        $field = 'field' . $suffix;
        $field_definition = 'field_definition' . $suffix;

        if (is_null($field_name_prefix)) {
            $field_name_prefix = $this->randomMachineName();
        }

        $this->fieldTestData->$field_name = Unicode::strtolower($field_name_prefix . '_field_name' . $suffix);
        $this->fieldTestData->$field_storage = FieldStorageConfig::create([
        'field_name' => $this->fieldTestData->$field_name,
        'entity_type' => $entity_type,
        'type' => $field_type,
        'cardinality' => $cardinality,
        'settings' => $field_storage_settings,
        ]);
        $this->fieldTestData->$field_storage->save();
        $this->fieldTestData->$field_storage_uuid = $this->fieldTestData->$field_storage->uuid();
        $this->fieldTestData->$field_definition = [
        'field_storage' => $this->fieldTestData->$field_storage,
        'bundle' => $bundle,
        'label' => $this->randomMachineName() . '_label',
        'description' => $this->randomMachineName() . '_description',
        'settings' => $field_settings,
        ];
        $this->fieldTestData->$field = FieldConfig::create($this->fieldTestData->$field_definition);
        $this->fieldTestData->$field->save();

        return $this->fieldTestData->$field_name;
    }

    protected function assertCreatedWithField($fieldIntended, $fieldExpected = null)
    {
        if (is_null($fieldExpected)) {
            $fieldExpected = $fieldIntended;
        }
        $entity = $this->createTestEntity($fieldIntended);
        $this->assertValidField($entity);
        $this->assertFieldValues($entity, $fieldExpected);
    }

    protected function createTestEntity($fieldIntended, $entity_type = null, $bundle = null)
    {
        if (is_null($entity_type)) {
            $entity_type = $this->entityType;
        }
        $this->fieldName = $this->createFieldForDriverTest(
            $this->fieldType,
            count($fieldIntended),
            $this->fieldSettings,
            $this->fieldStorageSettings,
            '',
            $entity_type,
            $bundle
        );

        // Create the entity with the field values.
        $name = $this->randomString();
        $fields = [
        'name' => $name,
        $this->fieldName => $fieldIntended,
        ];
        $bundle_key = \Drupal::entityManager()->getDefinition($entity_type)->getKey('bundle');
        if (!empty($bundle)) {
            $fields[$bundle_key] = $bundle;
        }
        // @todo This can be changed to DriverFieldDrupal8::create once it is no
        // longer important to show field plugins working with deprecated driver
        // methods.
        $this->driver->createEntity($entity_type, (object) $fields);

        // Load the created entity.
        // The driverfield test plugin mutates the name of entity_test entities.
        $processedName = $name;
        if ($entity_type === 'entity_test' || $entity_type === 'entity_test_with_bundle') {
          $processedName = "now" . $name . "processed";
        }

        $this->storage = \Drupal::entityTypeManager()->getStorage($entity_type);
        $entities = $this->storage->loadByProperties(['name' => $processedName]);
        $this->assertEquals(1, count($entities));
        $entity = reset($entities);
        $entity = $this->reloadEntity($entity);
        return $entity;
    }

    protected function assertValidField($entity)
    {
        // Make sure the saved data is valid. Drupal does this when forms are saved,
        // but not when values are set by entity API.
        $field = $entity->get($this->fieldName);
        $this->assertEmpty($field->validate(), format_string("Test field has validation constraint violation. Values are: \n @values", ['@values' => print_r($field->getValue(), true)]));
    }

    protected function assertFieldValues($entity, $expectedValues)
    {
        $field = $entity->get($this->fieldName);
        $actualValues = $field->getValue();
        foreach ($expectedValues as $valueNumber => $expectedValue) {
            // If there is only one expected column, don't require it an array.
            if (is_array($expectedValue)) {
                foreach ($expectedValue as $property => $value) {
                    $this->assertEquals($value, $actualValues[$valueNumber][$property]);
                }
            } else {
                $this->assertEquals($expectedValue, $actualValues[$valueNumber]['value']);
            }
        }
    }
}
