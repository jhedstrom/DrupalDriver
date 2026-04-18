<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: inspect Drupal field definitions.
 */
interface FieldCapabilityInterface {

  /**
   * Checks whether the named field exists on an entity type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE when the field exists on the entity type.
   */
  public function isField(string $entity_type, string $field_name): bool;

  /**
   * Checks whether the named field is a base field on an entity type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE when the field is a base field.
   */
  public function isBaseField(string $entity_type, string $field_name): bool;

}
