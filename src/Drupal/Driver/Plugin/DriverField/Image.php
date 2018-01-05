<?php
namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginBase;

/**
 * A driver field plugin for image fields.
 *
 * @DriverField(
 *   id = "image",
 *   fieldTypes = {
 *     "image",
 *   },
 *   weight = -100,
 * )
 */
class Image extends DriverFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function processValue($value) {
    $data = file_get_contents($value);
    if (FALSE === $data) {
      throw new \Exception("Error reading file");
    }

    /* @var \Drupal\file\FileInterface $file */
    $file = file_save_data(
      $data,
      'public://' . uniqid() . '.jpg');

    if (FALSE === $file) {
      throw new \Exception("Error saving file");
    }

    $file->save();

    $return = array(
      'target_id' => $file->id(),
      'alt' => 'Behat test image',
      'title' => 'Behat test image',
    );
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function processValues($values) {
    // @todo this field handler was buggy: it expected an array input, unlike all
    // other handlers, but only processed the first value..
    $processed = [];
    $processed[] = $this->processValue($values[0]);
    return $processed;
  }
}