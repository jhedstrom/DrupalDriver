<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'address' fields.
 */
class AddressHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function normalise(mixed $values): array {
    if ($values === []) {
      return [];
    }

    $visible_fields = $this->getVisibleAddressFields();

    if (is_string($values)) {
      return [$this->normaliseDelta($values, $visible_fields)];
    }

    if (!is_array($values)) {
      throw new \InvalidArgumentException(sprintf('Address field value must be a string or array. Got %s.', get_debug_type($values)));
    }

    // A top-level list of scalars is a single positional address (the
    // visible-field positions), not a multi-delta list. Only a list whose
    // first element is an array iterates as multi-delta.
    $is_list_of_records = array_is_list($values) && is_array($values[0] ?? NULL);

    if (!$is_list_of_records) {
      return [$this->normaliseDelta($values, $visible_fields)];
    }

    $records = [];

    foreach ($values as $value) {
      $records[] = $this->normaliseDelta($value, $visible_fields);
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    // 'available_countries' is empty when the field accepts every country;
    // 'reset([])' returns FALSE, which would land a boolean in storage.
    // Leave 'country_code' unset in that case so the field's own default
    // wins instead.
    $available = $this->fieldConfig->getSettings()['available_countries'] ?? [];

    foreach ($records as &$record) {
      if (!isset($record['country_code']) && $available !== []) {
        $record['country_code'] = reset($available);
      }
    }

    return $records;
  }

  /**
   * Returns address sub-fields that are not hidden by field overrides.
   *
   * @return array<int, string>
   *   Visible address field names.
   */
  protected function getVisibleAddressFields(): array {
    $fields = [
      'given_name',
      'additional_name',
      'family_name',
      'organization',
      'address_line1',
      'address_line2',
      'postal_code',
      'sorting_code',
      'locality',
      'administrative_area',
      'country_code',
    ];

    $overrides = $this->fieldConfig->getSettings()['field_overrides'];

    foreach ($overrides as $key => $setting) {
      if ($setting['override'] !== 'hidden') {
        continue;
      }

      // Convert camelCase override keys to snake_case field names.
      $snake_key = strtolower((string) preg_replace('/([A-Z])/', '_$1', (string) $key));
      $index = array_search($snake_key, $fields, TRUE);

      if ($index !== FALSE) {
        unset($fields[$index]);
      }
    }

    return array_values($fields);
  }

  /**
   * Folds one address value into a keyed sub-field array.
   *
   * @param mixed $value
   *   A single address value (string or array).
   * @param array<int, string> $visible_fields
   *   The list of visible address field names.
   *
   * @return array<string, mixed>
   *   A keyed array of address field values.
   */
  protected function normaliseDelta(mixed $value, array $visible_fields): array {
    if (is_string($value)) {
      return [reset($visible_fields) => $value];
    }

    $normalised = [];
    $position = 0;

    foreach ($value as $key => $field_value) {
      if (in_array($key, $visible_fields, TRUE)) {
        $normalised[$key] = $field_value;
        continue;
      }

      if (!is_numeric($key)) {
        throw new \RuntimeException(sprintf('Invalid address sub-field key: %s.', $key));
      }

      if (!isset($visible_fields[$position])) {
        throw new \RuntimeException(sprintf('Too many address sub-field values supplied; only %d visible fields available.', count($visible_fields)));
      }

      $normalised[$visible_fields[$position]] = $field_value;
      $position++;
    }

    return $normalised;
  }

}
