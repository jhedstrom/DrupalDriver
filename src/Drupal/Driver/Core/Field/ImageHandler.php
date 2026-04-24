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
   */
  public function expand($values): array {
    $file_path = $values[0];

    $file = $this->resolveExistingFile($file_path) ?? $this->uploadAndSave($file_path);

    return [
      'target_id' => $file->id(),
      'alt' => $values['alt'] ?? NULL,
      'title' => $values['title'] ?? NULL,
    ];
  }

}
