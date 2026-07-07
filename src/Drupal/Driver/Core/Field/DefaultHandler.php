<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Fallback handler for field types with no dedicated handler.
 *
 * Relays the normalised records to storage verbatim. It is the resolved
 * handler for any field type without a registered handler class. 'Core' asks
 * the field classifier whether the field is default-expandable before it falls
 * back here (see 'FieldClassifierInterface::fieldDefaultExpandReason()') and
 * rejects a field this handler cannot marshal - an entity-reference target or a
 * complex/nested value - so by the time this handler runs the field is known to
 * be a plain-scalar shape safe to pass through.
 *
 * See 'src/Drupal/Driver/Core/Field/README.md' for the full handler-selection
 * table.
 */
class DefaultHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    return $records;
  }

}
