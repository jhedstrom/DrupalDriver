<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * A base handler for the various types of Date fields in Drupal 7.
 */
abstract class AbstractDateHandler extends AbstractHandler {

  /**
   * The format in which the dates are saved in Drupal's database.
   *
   * @var string
   */
  protected $dateFormat = NULL;

  /**
   * Converts a date string into the format expected by Drupal.
   *
   * @return string
   *   The re-formatted date string.
   */
  protected function formatDateValue($value) {

    if (empty($this->dateFormat)) {
      return $value;
    }

    $date = new \DateTime($value);
    return $date->format($this->dateFormat);
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
