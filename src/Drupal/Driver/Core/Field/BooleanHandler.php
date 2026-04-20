<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for the 'boolean' field type.
 *
 * Scenarios authored against a BDD driver read naturally when boolean columns
 * carry human words ('Yes', 'Published') rather than the raw 1/0 the Drupal
 * field API stores. This handler resolves an incoming value in two stages:
 *
 *  1. The field's own 'on_label' / 'off_label' settings, compared
 *     case-insensitively. These are the labels the site builder configured;
 *     they already reflect the site's active language, so translated sites
 *     get translation matching for free.
 *  2. A canonical allow-list via 'filter_var(FILTER_VALIDATE_BOOLEAN)':
 *     '1', 'true', 'on', 'yes' map to 1; '0', 'false', 'off', 'no', '' map
 *     to 0. Case-insensitive.
 *
 * A value that matches neither throws a descriptive 'RuntimeException' listing
 * both the field-specific labels and the canonical set, so scenario authors
 * get an actionable error instead of a silent coercion to FALSE.
 *
 * Subclasses in a 'Core{N}\Field\' tree can override 'resolveBoolean()' to
 * extend the mapping (e.g. site-specific synonyms) without reimplementing the
 * per-delta loop.
 */
class BooleanHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand(mixed $values): array {
    $settings = $this->fieldConfig->getSettings();
    $on_label = (string) ($settings['on_label'] ?? '');
    $off_label = (string) ($settings['off_label'] ?? '');

    $resolved = [];

    foreach ((array) $values as $value) {
      $resolved[] = $this->resolveBoolean((string) $value, $on_label, $off_label);
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
