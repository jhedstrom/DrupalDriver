<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;

/**
 * Default Drupal 11 value-shape classifier.
 *
 * See 'src/Drupal/Driver/Core/Field/README.md' for the value-shape axis and how
 * 'Core' consumes it during handler selection.
 */
class FieldShapeClassifier implements FieldShapeClassifierInterface {

  /**
   * {@inheritdoc}
   */
  public function fieldIsEntityReference(FieldStorageDefinitionInterface $storage): bool {
    foreach ($this->storedProperties($storage) as $definition) {
      if ($definition instanceof DataReferenceTargetDefinition) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsComplexValue(FieldStorageDefinitionInterface $storage): bool {
    foreach ($this->storedProperties($storage) as $definition) {
      if ($definition instanceof ComplexDataDefinitionInterface || $definition instanceof ListDataDefinitionInterface) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Yields a field's stored (non-computed) property definitions.
   *
   * Computed properties are storage-derived, not author-supplied, so they never
   * bear on whether the caller can express the field as a plain scalar.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage
   *   The field storage definition to inspect.
   *
   * @return iterable<\Drupal\Core\TypedData\DataDefinitionInterface>
   *   The stored property definitions.
   */
  protected function storedProperties(FieldStorageDefinitionInterface $storage): iterable {
    foreach ($storage->getPropertyDefinitions() as $definition) {
      if (!$definition->isComputed()) {
        yield $definition;
      }
    }
  }

}
