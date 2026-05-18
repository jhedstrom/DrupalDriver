<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Image field handler for Drupal 8.
 *
 * Extends FileHandler to inherit the resolve-existing-managed-file lookup
 * (by URI or bare basename) and the upload-and-save fallback. Overrides
 * expand() to return the image-specific shape ('target_id', 'alt', 'title').
 */
class ImageHandler extends FileHandler {

  /**
   * {@inheritdoc}
   *
   * Canonical contract: '$values' is a list of records keyed by column name
   * ('target_id', 'alt', 'title'). Returns the same shape with 'target_id'
   * resolved from path/URI/basename to a File entity id. Callers that hold
   * scalar paths must wrap them into '['target_id' => $path]' records.
   */
  public function expand($values): array {
    $expanded = [];

    foreach ($values as $record) {
      $file_path = (string) $record['target_id'];
      $file = $this->resolveExistingFile($file_path) ?? $this->uploadAndSave($file_path);

      $expanded[] = [
        'target_id' => $file->id(),
        'alt' => $record['alt'] ?? NULL,
        'title' => $record['title'] ?? NULL,
      ];
    }

    return $expanded;
  }

}
