<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * File field handler for Drupal 8.
 */
class FileHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $data = file_get_contents($values[0]);
    if (FALSE === $data) {
      throw new \Exception("Error reading file");
    }

    $ext = pathinfo($values[0], PATHINFO_EXTENSION);
    $ext = !empty($ext) ? '.' . $ext : '';

    /* @var \Drupal\file\FileInterface $file */
    $file = file_save_data(
        $data,
        'public://' . uniqid() . $ext);

    if (FALSE === $file) {
      throw new \Exception("Error saving file");
    }

    $file->save();

    $return = array(
      'target_id' => $file->id(),
      'display' => '1',
      'description' => 'Behat test file',
    );
    return $return;
  }

}
