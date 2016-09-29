<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * A base handler for the various types of Date fields in Drupal 7.
 */
abstract class AbstractDateHandler extends AbstractHandler {

  /**
   * Get the format in which the dates are saved in Drupal's database.
   *
   * @return string
   *   The format in which the dates are saved in Drupal's database.
   */
  abstract protected function getDateFormat();

  /**
   * Converts a date string into the format expected by Drupal.
   *
   * @return string
   *   The re-formatted date string.
   */
  protected function formatDateValue($value) {

    $date = new \DateTime($value);
    return $date->format($this->getDateFormat());
  }

  /**
   * {@inheritdoc}
   */
  public function expand($values) {

    $return = array();
    if (isset($this->fieldInfo['columns']['value2'])) {
      foreach ($values as $value) {
        $return[$this->language][] = array(
          'value' => $this->formatDateValue($value[0]),
          'value2' => $this->formatDateValue($value[1]),
        );
      }
    }
    else {
      foreach ($values as $value) {
        $return[$this->language][] = array(
          'value' => $this->formatDateValue($value),
        );
      }
    }
    return $return;
  }

}
