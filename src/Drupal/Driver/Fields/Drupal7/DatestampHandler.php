<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Datestamp field handler for Drupal 7.
 */
class DatestampHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = array();
    if (isset($this->fieldInfo['columns']['value2'])) {
      foreach ($values as $value) {
        $return[$this->language][] = array(
          'value' => strtotime($value[0]),
          'value2' => strtotime($value[1]),
        );
      }
    }
    else {
      foreach ($values as $value) {
        $return[$this->language][] = array('value' => strtotime($value));
      }
    }
    return $return;
  }

}
