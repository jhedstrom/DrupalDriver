<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Address field handler for Drupal 8.
 */
class AddressHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $visible_fields = $this->getVisibleAddressFields();
    $result = [];

    foreach ($values as $value) {
      $result[] = $this->normaliseDelta($value, $visible_fields);
    }

    return $result;
  }

  /**
   * Returns address sub-fields that are not hidden by field overrides.
   */
  protected function getVisibleAddressFields() {
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
      $snake_key = strtolower(preg_replace('/([A-Z])/', '_$1', $key));
      $index = array_search($snake_key, $fields, TRUE);

      if ($index !== FALSE) {
        unset($fields[$index]);
      }
    }

    return array_values($fields);
  }

  /**
   * Normalises a single address delta into a keyed array.
   */
  protected function normaliseDelta($value, array $visible_fields) {
    if (is_string($value)) {
      return [reset($visible_fields) => $value];
    }

    $normalised = [];
    $position = 0;

    foreach ($value as $key => $field_value) {
      if (in_array($key, $visible_fields, TRUE)) {
        $normalised[$key] = $field_value;
      }
      elseif (is_numeric($key)) {
        $normalised[$visible_fields[$position]] = $field_value;
        $position++;
      }
      else {
        throw new \RuntimeException(sprintf('Invalid address sub-field key: %s.', $key));
      }
    }

    if (!isset($normalised['country_code'])) {
      $normalised['country_code'] = reset($this->fieldConfig->getSettings()['available_countries']);
    }

    return $normalised;
  }

}
