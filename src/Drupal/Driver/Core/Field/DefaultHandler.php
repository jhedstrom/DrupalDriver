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
 * Classifies a field by its stored property definitions. When every stored
 * property is a plain scalar the caller can author verbatim, the normalised
 * records are relayed to storage unchanged - so any field whose columns hold
 * literal values (text, formatted text, colours, and future contrib fields of
 * the same shape) needs no dedicated handler at all.
 *
 * When a stored property needs a translation the default cannot perform
 * generically - an entity-reference target that must be resolved from a label
 * to an id, a datetime string that needs timezone-aware formatting, or a
 * complex/nested value - it throws with a message naming the field and the
 * offending property, directing the contributor to register a dedicated
 * handler. Detection is proactive: these translations fail silently (a label
 * persisted as a bogus id, a timezone-shifted date), so the field is rejected
 * before expansion rather than after a corrupt save.
 *
 * See 'src/Drupal/Driver/Core/Field/README.md' for the full handler-selection
 * table.
 */
class DefaultHandler extends AbstractHandler {

  /**
   * Typed-data types whose stored form differs from natural author input.
   *
   * These hold ISO 8601 strings the author expresses in a friendlier shape (a
   * human date, a site-timezone time), so relaying a record verbatim would
   * persist malformed or timezone-shifted data. A dedicated handler owns the
   * conversion.
   */
  protected const TRANSLATED_DATA_TYPES = [
    'datetime_iso8601',
    'duration_iso8601',
  ];

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
   * property is a plain scalar the caller authors as-is. A property that needs
   * a translation the default cannot perform - an entity-reference target, a
   * datetime/duration string, or a complex/nested value - makes the field
   * ineligible and names itself in the returned reason.
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

      if (in_array($definition->getDataType(), static::TRANSLATED_DATA_TYPES, TRUE)) {
        return sprintf('property "%s" is a "%s" value that needs timezone-aware formatting', $name, $definition->getDataType());
      }
    }

    return NULL;
  }

}
