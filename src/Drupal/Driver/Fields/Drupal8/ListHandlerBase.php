<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Base class for List* field types.
 *
 * This allows use of allowed value labels rather than their storage value.
 */
abstract class ListHandlerBase extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = [];

    // Load allowed values from field storage.
    $allowedValues = $this->fieldInfo->getSetting('allowed_values');
    foreach ((array) $values as $value) {
      // Determine if a label matching the value is found.
      $key = array_search($value, $allowedValues);
      if ($key !== FALSE) {
        // Set the return to use the key instead of the value.
        $return[] = $key;
      }
    }

    return $return ?: $values;
  }

}
