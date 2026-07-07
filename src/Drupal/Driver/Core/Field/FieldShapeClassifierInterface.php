<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Classifies a field's stored value shape for handler selection.
 *
 * Where 'FieldClassifierInterface' answers the pipeline-entry (F-row) question
 * from a field's origin and storage profile, this answers the orthogonal
 * value-shape question the README calls a "handler-selection input": is a
 * field's stored value a plain scalar the default handler can relay, or a shape
 * that needs a dedicated handler? Both predicates read only the storage
 * definition's stored (non-computed) property definitions and enumerate no
 * field-type or data-type strings, so a datetime, boolean, or list column is
 * neither an entity reference nor complex - it is a plain scalar the default
 * relays, and value translation for it belongs in a dedicated handler.
 *
 * See 'src/Drupal/Driver/Core/Field/README.md' for the value-shape axis and how
 * 'Core' consumes it during handler selection.
 */
interface FieldShapeClassifierInterface {

  /**
   * Whether a stored property references another entity by id.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage
   *   The field storage definition to inspect.
   *
   * @return bool
   *   TRUE when a non-computed property is a 'DataReferenceTargetDefinition' -
   *   the caller supplies a label, path, or name a dedicated handler must
   *   resolve to an id the author cannot know.
   */
  public function fieldIsEntityReference(FieldStorageDefinitionInterface $storage): bool;

  /**
   * Whether a stored property holds a complex or nested value.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage
   *   The field storage definition to inspect.
   *
   * @return bool
   *   TRUE when a non-computed property is a 'ComplexDataDefinitionInterface'
   *   (e.g. a map) or a 'ListDataDefinitionInterface' - there is no single
   *   scalar shape for the default handler to relay.
   */
  public function fieldIsComplexValue(FieldStorageDefinitionInterface $storage): bool;

}
