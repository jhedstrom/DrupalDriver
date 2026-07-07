<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;

/**
 * Self-classifying fallback handler for field types with no dedicated handler.
 *
 * Stays deliberately generic: it knows nothing about specific field types or
 * data-type strings. It relays the normalised records to storage verbatim
 * unless a stored property is one the generic type system says cannot be
 * authored as a plain scalar - an entity-reference target (whose id is
 * system-assigned) or a complex/nested value (which has no single scalar
 * shape). Everything else, including datetime strings, booleans, and list
 * keys, is a scalar the default relays as-is.
 *
 * A field whose author-facing input differs from its stored scalar - a
 * timezone-relative date, a boolean label, an allowed-value label - is served
 * by a dedicated handler that performs the translation, not by teaching the
 * default about that type. Rejection is proactive: the two structural cases
 * fail silently (a label persisted as a bogus id, a nested value flattened),
 * so the field is refused before expansion rather than after a corrupt save,
 * with a message naming the field and the offending property.
 *
 * See 'src/Drupal/Driver/Core/Field/README.md' for the full handler-selection
 * table.
 */
class DefaultHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $reason = static::unsupportedPropertyReason($this->fieldInfo);

    if ($reason !== NULL) {
      throw new \RuntimeException(sprintf(
        'No dedicated handler is registered for field "%s" (type "%s") on entity type "%s" bundle "%s", and DefaultHandler cannot marshal it: %s. Register a dedicated handler via Core::registerFieldHandler().',
        $this->fieldInfo->getName(),
        $this->fieldInfo->getType(),
        $this->fieldInfo->getTargetEntityTypeId(),
        $this->fieldConfig->getTargetBundle() ?? '(none)',
        $reason,
      ));
    }

    return $records;
  }

  /**
   * Explains why a field cannot ride the default pass-through, or NULL if safe.
   *
   * Inspects the storage definition's stored (non-computed) properties. The
   * default relays records verbatim, which is only correct when every stored
   * property is a plain scalar the caller authors as-is. The generic type
   * system exposes two shapes that are not: an entity-reference target, whose
   * id the caller cannot know, and a complex or nested value, which has no
   * single scalar to relay. Either makes the field ineligible and names itself
   * in the returned reason. No field-type or data-type string is enumerated
   * here, so the default never rejects a field merely for being a datetime, a
   * boolean, or a list - those are scalars a dedicated handler translates.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage
   *   The field storage definition to classify.
   *
   * @return string|null
   *   A human-readable reason the field needs a dedicated handler, or NULL
   *   when the default pass-through is safe.
   */
  public static function unsupportedPropertyReason(FieldStorageDefinitionInterface $storage): ?string {
    foreach ($storage->getPropertyDefinitions() as $name => $definition) {
      if ($definition->isComputed()) {
        continue;
      }

      if ($definition instanceof DataReferenceTargetDefinition) {
        return sprintf('property "%s" is an entity-reference target that must be resolved to an id', $name);
      }

      if ($definition instanceof ComplexDataDefinitionInterface || $definition instanceof ListDataDefinitionInterface) {
        return sprintf('property "%s" holds a complex or nested value', $name);
      }
    }

    return NULL;
  }

}
