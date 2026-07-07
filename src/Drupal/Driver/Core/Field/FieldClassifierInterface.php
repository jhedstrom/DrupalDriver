<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Classifies Drupal fields into the nine mutually exclusive F-row categories.
 *
 * Each predicate answers "is this field in F{N}?" for one row of the truth
 * table, based only on the field's declaration and storage profile. The
 * classifier expresses no opinion about what a consumer does with the
 * classification - that decision lives in the consumer (e.g. 'Core' decides
 * which F-rows participate in its handler-expansion pipeline).
 *
 * See 'src/Drupal/Driver/Core/Field/README.md' for the full truth table, the
 * example fields per row, the handler-selection sub-table, and how 'Core'
 * consumes the classifications.
 */
interface FieldClassifierInterface {

  /**
   * F1: entity-type-wide base field with standard storage.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE when the field is in 'getBaseFieldDefinitions()', not computed, and
   *   not custom-storage.
   */
  public function fieldIsBaseStandard(string $entity_type, string $field_name): bool;

  /**
   * F2: entity-type-wide base field, computed, read-only.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE when the field is in 'getBaseFieldDefinitions()', computed, and
   *   'isReadOnly()' returns TRUE.
   */
  public function fieldIsBaseComputedReadOnly(string $entity_type, string $field_name): bool;

  /**
   * F3: entity-type-wide base field, computed, writable with side-effects.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE when the field is in 'getBaseFieldDefinitions()', computed, and
   *   'isReadOnly()' returns FALSE.
   */
  public function fieldIsBaseComputedWritable(string $entity_type, string $field_name): bool;

  /**
   * F4: entity-type-wide base field with custom storage.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE when the field is in 'getBaseFieldDefinitions()' and
   *   'hasCustomStorage()' returns TRUE.
   */
  public function fieldIsBaseCustomStorage(string $entity_type, string $field_name): bool;

  /**
   * F5: configurable field (FieldStorageConfig + FieldConfig).
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE when the field is a 'FieldStorageConfig' instance in
   *   'getFieldStorageDefinitions()'.
   */
  public function fieldIsConfigurable(string $entity_type, string $field_name): bool;

  /**
   * F6: bundle-only field, computed, read-only.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string $bundle
   *   The bundle name.
   *
   * @return bool
   *   TRUE when the field is in 'getFieldDefinitions($entity_type, $bundle)',
   *   computed, read-only, and not in 'getBaseFieldDefinitions()'.
   */
  public function fieldIsBundleComputedReadOnly(string $entity_type, string $field_name, string $bundle): bool;

  /**
   * F7: bundle-only field, computed, writable with side-effects.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string $bundle
   *   The bundle name.
   *
   * @return bool
   *   TRUE when the field is in 'getFieldDefinitions($entity_type, $bundle)',
   *   computed, writable, and not in 'getBaseFieldDefinitions()'.
   */
  public function fieldIsBundleComputedWritable(string $entity_type, string $field_name, string $bundle): bool;

  /**
   * F8: bundle-only field with custom storage.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string $bundle
   *   The bundle name.
   *
   * @return bool
   *   TRUE when the field is in 'getFieldDefinitions($entity_type, $bundle)',
   *   custom storage, and not in 'getBaseFieldDefinitions()'.
   */
  public function fieldIsBundleCustomStorage(string $entity_type, string $field_name, string $bundle): bool;

  /**
   * F9: bundle-attached, storage-backed via 'hook_entity_field_storage_info()'.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string $bundle
   *   The bundle name.
   *
   * @return bool
   *   TRUE when the field is in 'getFieldStorageDefinitions()' but not a
   *   'FieldStorageConfig' instance and not in 'getBaseFieldDefinitions()',
   *   and is present in the bundle's field definitions.
   */
  public function fieldIsBundleStorageBacked(string $entity_type, string $field_name, string $bundle): bool;

}
