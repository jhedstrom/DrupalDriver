<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'boolean' fields.
 */
class BooleanHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $settings = $this->fieldConfig->getSettings();
    $on_label = (string) ($settings['on_label'] ?? '');
    $off_label = (string) ($settings['off_label'] ?? '');

    $resolved = [];

    foreach ($records as $record) {
      $resolved[] = $this->resolveBoolean((string) $record['value'], $on_label, $off_label);
    }

    return $resolved;
  }

  /**
   * Maps a raw value to 1 (true) or 0 (false).
   *
   * @param string $value
   *   The value to resolve.
   * @param string $on_label
   *   The field's configured on-state label, or '' if none.
   * @param string $off_label
   *   The field's configured off-state label, or '' if none.
   *
   * @return int
   *   1 or 0.
   *
   * @throws \RuntimeException
   *   When the value matches neither the field labels nor a canonical form.
   */
  protected function resolveBoolean(string $value, string $on_label, string $off_label): int {
    if ($on_label !== '' && strcasecmp($value, $on_label) === 0) {
      return 1;
    }

    if ($off_label !== '' && strcasecmp($value, $off_label) === 0) {
      return 0;
    }

    $canonical = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    if ($canonical === NULL) {
      throw new \RuntimeException(sprintf(
        'Cannot convert "%s" to a boolean. Accepted values: on label "%s", off label "%s", or any of 1/0, true/false, yes/no, on/off (case-insensitive).',
        $value,
        $on_label,
        $off_label,
      ));
    }

    return $canonical ? 1 : 0;
  }

}
