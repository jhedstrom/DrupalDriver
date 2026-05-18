<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Base class for field handlers.
 */
abstract class AbstractHandler implements FieldHandlerInterface {
  /**
   * Field storage definition.
   */
  protected FieldStorageDefinitionInterface $fieldInfo;

  /**
   * Field configuration definition.
   */
  protected FieldDefinitionInterface $fieldConfig;

  /**
   * Constructs an AbstractHandler object.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The simulated entity stub providing the bundle context.
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   *
   * @throws \Exception
   *   Thrown when the given field name does not exist on the entity.
   */
  public function __construct(EntityStubInterface $stub, string $entity_type, string $field_name) {
    if ($entity_type === '') {
      throw new \InvalidArgumentException('You must specify an entity type in order to parse entity fields.');
    }

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type);

    // Resolve the bundle: bundle key value > typed bundle > entity type
    // (single-bundle entities like 'user' use the entity type as the bundle).
    $bundle_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('bundle');
    $bundle = $entity_type;

    if ($bundle_key && !empty($stub->getValue($bundle_key))) {
      $bundle = (string) $stub->getValue($bundle_key);
    }
    elseif ($stub->getBundle() !== NULL && $stub->getBundle() !== '') {
      $bundle = $stub->getBundle();
    }

    $field_definitions = $entity_field_manager->getFieldDefinitions($entity_type, $bundle);

    if (empty($storage_definitions[$field_name]) || empty($field_definitions[$field_name])) {
      throw new \RuntimeException(sprintf('The field "%s" does not exist on entity type "%s" bundle "%s".', $field_name, $entity_type, $bundle));
    }

    $this->fieldInfo = $storage_definitions[$field_name];
    $this->fieldConfig = $field_definitions[$field_name];
  }

  /**
   * Normalises loose handler input into a canonical list of records.
   *
   * Consumers should not have to know whether a field is single- or
   * multi-column; this method accepts any of the natural shapes a caller
   * is likely to produce and returns a uniform 'array<int, array<string,
   * mixed>>' that 'expand()' implementations can iterate without sniffing.
   *
   * Recognised input shapes:
   *   - Bare scalar -> wrapped as a single record using the main property.
   *   - List of scalars -> each wrapped as a record.
   *   - Single keyed record -> wrapped in a one-element list.
   *   - List of records -> returned unchanged.
   *   - Mixed list of scalars and records -> scalars wrapped, records kept.
   *
   * The main property name (the column a bare scalar maps to) is pulled
   * from the field's storage definition - 'target_id' for image/file/
   * entity_reference, 'value' for datetime/boolean/list/text, 'uri' for
   * link, etc. Subclasses with custom shorthand (e.g. 'NameHandler's
   * 'Family, Given' string, 'AddressHandler's first-visible-field
   * shorthand) should override this method and call 'parent::normalise()'
   * for the residual cases they do not handle themselves.
   *
   * @param mixed $values
   *   Whatever shape the caller produced.
   *
   * @return array<int, array<string, mixed>>
   *   A canonical list of records.
   */
  protected function normalise(mixed $values): array {
    $main_property = $this->fieldInfo->getMainPropertyName();

    if (!is_array($values)) {
      return [[$main_property => $values]];
    }

    if ($values === []) {
      return [];
    }

    // '['foo.jpg', 'alt' => 'A']' is ambiguous: is 'foo.jpg' the main
    // value with 'alt' as an extra, or two separate deltas with one of
    // them named? Reject rather than silently picking one.
    $has_int_key = FALSE;
    $has_string_key = FALSE;

    foreach (array_keys($values) as $key) {
      if (is_int($key)) {
        $has_int_key = TRUE;
      }
      else {
        $has_string_key = TRUE;
      }
    }

    if ($has_int_key && $has_string_key) {
      throw new \InvalidArgumentException(sprintf(
        'Field value cannot mix positional and named keys at the top level. Got keys: %s. Pass either a list of values or a single keyed record, not both.',
        implode(', ', array_keys($values)),
      ));
    }

    if (!array_is_list($values)) {
      $records = [$values];
    }
    else {
      $records = [];

      foreach ($values as $value) {
        $records[] = is_array($value) ? $value : [$main_property => $value];
      }
    }

    // Every record must carry the main property key. A record without it
    // is almost always a caller mistake (omitted the path/value/uri and
    // left only the extras like 'alt' or 'format'); flag it here so the
    // handler does not silently dispatch on missing data.
    foreach ($records as $record) {
      if (!array_key_exists($main_property, $record)) {
        throw new \InvalidArgumentException(sprintf(
          'Field record must include the main property "%s". Got keys: %s.',
          $main_property,
          implode(', ', array_keys($record)) ?: '(none)',
        ));
      }
    }

    return $records;
  }

}
