<?php
namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal8Base;

/**
 * A driver field plugin for image fields.
 *
 * @DriverField(
 *   id = "image",
 *   version = 8,
 *   fieldTypes = {
 *     "image",
 *   },
 *   weight = -100,
 * )
 */
class ImageDrupal8 extends DriverFieldPluginDrupal8Base {

  /**
   * {@inheritdoc}
   */
  protected function processValue($value) {
    $data = file_get_contents($value['target_id']);
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

}