<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * ListBoolean field handler for Drupal 7.
 */
class ListBooleanHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = [];
    $allowedValues = $this->fieldInfo['settings']['allowed_values'];
    // If values are blank then use keys as value.
    foreach ($allowedValues as $key => $value) {
      if ($value == '') {
        $allowedValues[$key] = $key;
      }
    }
    $allowedValues = array_flip($allowedValues);
    foreach ($values as $value) {
      $return[$this->language][] = ['value' => $allowedValues[$value]];
    }
    return $return;
  }

}
