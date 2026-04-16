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
    $return = [];
    foreach ((array) $values as $value) {
      $filePath = is_array($value) ? $value['target_id'] ?? $value[0] : $value;
      $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
      $data = file_get_contents($filePath);

      if ($data === FALSE) {
        throw new \Exception(sprintf('Error reading file %s.', $filePath));
      }

      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::service('file.repository')
        ->writeData($data, 'public://' . uniqid() . '.' . $fileExtension);

      if ($file === FALSE) {
        throw new \Exception('Error saving file.');
      }

      $file->save();

      $return[] = [
        'target_id' => $file->id(),
        'display' => is_array($value) ? ($value['display'] ?? 1) : 1,
        'description' => is_array($value) ? ($value['description'] ?? '') : '',
      ];
    }
    return $return;
  }

}
