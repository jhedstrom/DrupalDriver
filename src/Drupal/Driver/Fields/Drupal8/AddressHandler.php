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
    $addressFields = [
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
      if (count($matches) > 0) {
        $fieldName = strtolower(substr_replace($key, '_', $matches[0][1], 0));
      }
      else {
        $fieldName = $key;
      }
      if ($value['override'] === 'hidden') {
        $removeKey = array_search($fieldName, $addressFields, TRUE);
        unset($addressFields[$removeKey]);
      }
    }
    // Re-index the Address Fields.
    $addressFields = array_values($addressFields);
    foreach ($values as $value) {
      $return_value = [];
      // If this delta value is a string, assign it to the first address
      // sub-field and move onto next delta.
      if (is_string($value)) {
        $firstSubField = reset($addressFields);
        $return_value[$firstSubField] = $value;
        continue;
      }
      if (is_array($value)) {
        $idx = 0;
        foreach ($value as $k => $v) {
          // If this key is a valid address sub-field, set it as-is.
          if (in_array($k, $addressFields, TRUE)) {
            $return_value[$k] = $v;
          }
          // Otherwise if the key is numeric, add the value sequentially
          // in the order of the available address sub-fields.
          elseif (is_numeric($k)) {
            $key = $addressFields[$idx];
            $return_value[$key] = $v;
            $idx++;
          }
          // Otherwise the key is invalid, throw error.
          else {
            throw new \RuntimeException("Invalid Address sub-field key: '$k'");
          }
        }
      }
      // If the country code has not been set yet, set it to the first
      // available country as configured in this instance of the field.
      if (!isset($return_value['country_code'])) {
        $return_value['country_code'] = reset($this->fieldConfig->getSettings()['available_countries']);
      }
      $return_values[] = $return_value;
    }
    return $return_values;
  }

}
