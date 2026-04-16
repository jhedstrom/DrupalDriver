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
    $returnValues = [];
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
    // Re-index the address fields.
    $addressFields = array_values($addressFields);
    foreach ($values as $value) {
      $returnValue = [];
      // If this delta value is a string, assign it to the first address
      // sub-field and move onto next delta.
      if (is_string($value)) {
        $firstSubField = reset($addressFields);
        $returnValue[$firstSubField] = $value;
        $returnValues[] = $returnValue;
        continue;
      }
      if (is_array($value)) {
        $idx = 0;
        foreach ($value as $k => $v) {
          // If this key is a valid address sub-field, set it as-is.
          if (in_array($k, $addressFields, TRUE)) {
            $returnValue[$k] = $v;
          }
          // Otherwise if the key is numeric, add the value sequentially
          // in the order of the available address sub-fields.
          elseif (is_numeric($k)) {
            $key = $addressFields[$idx];
            $returnValue[$key] = $v;
            $idx++;
          }
          else {
            throw new \RuntimeException(sprintf('Invalid address sub-field key: %s.', $k));
          }
        }
      }
      // If the country code has not been set, use the first available country
      // as configured in this field instance.
      if (!isset($returnValue['country_code'])) {
        $returnValue['country_code'] = reset($this->fieldConfig->getSettings()['available_countries']);
      }
      $returnValues[] = $returnValue;
    }
    return $returnValues;
  }

}
