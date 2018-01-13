<?php
namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal8Base;

/**
 * A driver field plugin for link fields.
 *
 * @DriverField(
 *   id = "link",
 *   version = 8,
 *   fieldTypes = {
 *     "link",
 *   },
 *   weight = -100,
 * )
 */
class LinkDrupal8 extends DriverFieldPluginDrupal8Base {

  /**
   * {@inheritdoc}
   */
  protected function assignPropertyNames($value) {
    // For links we support unkeyed arrays in which the first item is the title,
    // the second is the uri and third is options.
    $keyedValue = $value;
    if (!is_array($value)) {
      $keyedValue = ['uri' => $value];
    }
    elseif(count($value) === 1) {
      $keyedValue = ['uri' => end($value)];
    }
    // Convert unkeyed array.
    else {
      if (!isset($value['uri']) && isset($value[1])) {
        $keyedValue['uri'] = $value[1];
        unset($keyedValue[1]);
      }
      if (!isset($value['title']) && isset($value[0])) {
        $keyedValue['title'] = $value[0];
        unset($keyedValue[0]);
      }
      if (!isset($value['options']) && isset($value[2])) {
        $keyedValue['options'] = $value[2];
        unset($keyedValue[2]);
      }
    }
    if (!isset($keyedValue['uri'])) {
      throw new \Exception("Uri could not be identified from passed value: " . print_r($value, TRUE));
    }
    return $keyedValue;
  }

  /**
   * {@inheritdoc}
   */
  protected function processValue($value) {
    // 'options' is required to be an array, otherwise the utility class
    // Drupal\Core\Utility\UnroutedUrlAssembler::assemble() will complain.
    $options = [];
    if (!empty($value['options'])) {
      parse_str($value['options'], $options);
    }

    // Default title to uri.
    $title = $value['uri'];
    if (isset($value['title'])) {
      $title = $value['title'];
    }

    $processedValue = [
      'uri' => $value['uri'],
      'title' => $title,
      'options' => $options,
    ];
    return $processedValue;
  }
}