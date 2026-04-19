<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Supported Image field handler for Drupal 8+.
 *
 * Adapted from ImageHandler.
 *
 * @see \Drupal\Driver\Core\Field\ImageHandler
 * @see https://www.drupal.org/project/supported_image
 */
class SupportedImageHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    // Standardize single/multi-value input.
    if (is_string($values) || isset($values['target_id'])) {
      $values = [$values];
    }

    $images = [];

    foreach ($values as $value) {
      $file_path = (string) ($value['target_id'] ?? $value);
      $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
      $data = file_get_contents($file_path);

      if ($data === FALSE) {
        throw new \Exception("Error reading file");
      }

      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::service('file.repository')
        ->writeData($data, 'public://' . uniqid() . '.' . $file_extension);
      $file->save();

      $images[] = [
        'target_id' => $file->id(),
        'alt' => $value['alt'] ?? NULL,
        'title' => $value['title'] ?? NULL,
        'caption_value' => $value['caption_value'] ?? NULL,
        'caption_format' => $value['caption_format'] ?? NULL,
        'attribution_value' => $value['attribution_value'] ?? NULL,
        'attribution_format' => $value['attribution_format'] ?? NULL,
      ];
    }

    return $images;
  }

}
