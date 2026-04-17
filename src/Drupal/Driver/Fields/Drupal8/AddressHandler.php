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
    $return_values = [];
    $overrides = $this->fieldConfig->getSettings()['field_overrides'];
    $address_fields = [
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
    // Any overrides that set field inputs to hidden will be skipped.
    foreach ($overrides as $key => $value) {
      preg_match('/[A-Z]/', $key, $matches, PREG_OFFSET_CAPTURE);
      $field_name = $matches !== [] ? strtolower(substr_replace($key, '_', $matches[0][1], 0)) : $key;
      if ($value['override'] === 'hidden') {
        $remove_key = array_search($field_name, $address_fields, TRUE);
        unset($address_fields[$remove_key]);
      }
    }
    // Re-index the address fields.
    $address_fields = array_values($address_fields);
    foreach ($values as $value) {
      $return_value = [];
      // If this delta value is a string, assign it to the first address
      // sub-field and move onto next delta.
      if (is_string($value)) {
        $first_sub_field = reset($address_fields);
        $return_value[$first_sub_field] = $value;
        $return_values[] = $return_value;
        continue;
      }
      if (is_array($value)) {
        $idx = 0;
        foreach ($value as $k => $v) {
          // If this key is a valid address sub-field, set it as-is.
          if (in_array($k, $address_fields, TRUE)) {
            $return_value[$k] = $v;
          }
          // Otherwise if the key is numeric, add the value sequentially
          // in the order of the available address sub-fields.
          elseif (is_numeric($k)) {
            $key = $address_fields[$idx];
            $return_value[$key] = $v;
            $idx++;
          }
          else {
            throw new \RuntimeException(sprintf('Invalid address sub-field key: %s.', $k));
          }
        }
      }
      // If the country code has not been set, use the first available country
      // as configured in this field instance.
      if (!isset($return_value['country_code'])) {
        $return_value['country_code'] = reset($this->fieldConfig->getSettings()['available_countries']);
      }
      $return_values[] = $return_value;
    }
    return $return_values;
  }

}
