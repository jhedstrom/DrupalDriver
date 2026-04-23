<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Fallback handler for field types that have no dedicated handler.
 *
 * Only correct for H1 (single-column scalar) fields. See
 * 'src/Drupal/Driver/Core/Field/README.md' for the full handler-selection
 * table and the loud-failure policy this class enforces.
 */
class DefaultHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand(mixed $values): array {
    $columns = $this->fieldInfo->getColumns();

    if (count($columns) !== 1 || !array_key_exists('value', $columns)) {
      throw new \RuntimeException(sprintf(
        'No dedicated handler is registered for field "%s" (type "%s") on entity type "%s" bundle "%s", and DefaultHandler cannot marshal it: the field has %d column(s) (%s) and DefaultHandler only supports single-column scalar fields keyed by "value". Implement a dedicated handler for this field type and register it via Core::registerFieldHandler().',
        $this->fieldInfo->getName(),
        $this->fieldInfo->getType(),
        $this->fieldInfo->getTargetEntityTypeId(),
        $this->fieldConfig->getTargetBundle() ?? '(none)',
        count($columns),
        implode(', ', array_keys($columns)),
      ));
    }

    return (array) $values;
  }

}
