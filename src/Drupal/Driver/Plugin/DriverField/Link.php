<?php
namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginBase;

/**
 * A driver field plugin for link fields.
 *
 * @DriverField(
 *   id = "link",
 *   fieldTypes = {
 *     "link",
 *   },
 *   weight = -100,
 * )
 */
class Link extends DriverFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function processValue($value) {
    // @todo add support for handling named column keys,
    // not just assuming the first one is title and second is uri.

    // 'options' is required to be an array, otherwise the utility class
    // Drupal\Core\Utility\UnroutedUrlAssembler::assemble() will complain.
    $options = [];
    if (!empty($value[2])) {
      parse_str($value[2], $options);
    }
    $processedValue = [
      'options' => $options,
      'title' => $value[0],
      'uri' => $value[1],
    ];
    return $processedValue;
  }
}