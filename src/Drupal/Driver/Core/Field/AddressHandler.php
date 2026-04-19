<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Address field handler for Drupal 8.
 */
class AddressHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $visible_fields = $this->getVisibleAddressFields();
    $result = [];

    foreach ($values as $value) {
      $result[] = $this->normaliseDelta($value, $visible_fields);
    }

    return $result;
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
   * Normalises a single address delta into a keyed array.
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

    if (!isset($normalised['country_code'])) {
      $normalised['country_code'] = reset($this->fieldConfig->getSettings()['available_countries']);
    }

    return $normalised;
  }

}
