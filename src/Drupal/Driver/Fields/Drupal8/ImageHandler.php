<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Image field handler for Drupal 8.
 */
class ImageHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $data = file_get_contents($values[0]);
    if (FALSE === $data) {
      throw new \Exception("Error reading file");
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = \Drupal::service('file.repository')->writeData($data, 'public://' . uniqid() . '.jpg');

    if (FALSE === $file) {
      throw new \Exception("Error saving file");
    }

    $file->save();

    $return = [
      'target_id' => $file->id(),
      'alt' => 'Behat test image',
      'title' => 'Behat test image',
    ];
    return $return;
  }

}
