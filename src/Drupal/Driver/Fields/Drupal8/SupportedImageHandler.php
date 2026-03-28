<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Supported Image field handler for Drupal 8+.
 *
 * Adapted from ImageHandler.
 *
 * @see \Drupal\Driver\Fields\Drupal8\ImageHandler
 * @see https://www.drupal.org/project/supported_image
 */
class SupportedImageHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return_values = [];

    // Standardize single/multi-value input.
    if (is_string($values) || isset($values['target_id'])) {
      $values = [$values];
    }

    foreach ($values as $value) {
      $file_path = (string) ($value['target_id'] ?? $value);
      $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
      $data = file_get_contents((string) $file_path);

      if ($data === FALSE) {
        throw new \Exception("Error reading file");
      }

      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::service('file.repository')
        ->writeData($data, 'public://' . uniqid() . ".$file_extension");

      if ($file === FALSE) {
        throw new \Exception("Error saving file");
      }

      $file->save();

      $return_values[] = [
        'target_id' => $file->id(),
        'alt' => $value['alt'] ?? NULL,
        'title' => $value['title'] ?? NULL,
        'caption_value' => $value['caption_value'] ?? NULL,
        'caption_format' => $value['caption_format'] ?? NULL,
        'attribution_value' => $value['attribution_value'] ?? NULL,
        'attribution_format' => $value['attribution_format'] ?? NULL,
      ];
    }

    return $return_values;
  }

}
