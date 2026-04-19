<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * File field handler for Drupal 8.
 */
class FileHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $files = [];

    foreach ((array) $values as $value) {
      $is_array = is_array($value);
      $file_path = (string) ($is_array ? $value['target_id'] ?? $value[0] : $value);
      $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
      $data = file_get_contents($file_path);

      if ($data === FALSE) {
        throw new \Exception(sprintf('Error reading file %s.', $file_path));
      }

      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::service('file.repository')
        ->writeData($data, 'public://' . uniqid() . '.' . $file_extension);
      $file->save();

      $files[] = [
        'target_id' => $file->id(),
        'display' => $is_array ? ($value['display'] ?? 1) : 1,
        'description' => $is_array ? ($value['description'] ?? '') : '',
      ];
    }

    return $files;
  }

}
