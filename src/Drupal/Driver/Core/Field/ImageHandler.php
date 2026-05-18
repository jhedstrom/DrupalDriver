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
    // Normalise three accepted shapes into a list of records:
    // - ['foo.jpg'] (scalar mode)
    // - ['foo.jpg', 'alt' => 'A', 'title' => 'B'] (legacy flat positional)
    // - [['target_id' => 'foo.jpg', 'alt' => 'A', 'title' => 'B']] (compound
    //   mode from EntityFieldParser row 16).
    $records = (isset($values[0]) && is_array($values[0])) ? $values : [$values];

    $expanded = [];

    foreach ($records as $record) {
      $file_path = (string) ($record['target_id'] ?? $record[0] ?? '');
      $file = $this->resolveExistingFile($file_path) ?? $this->uploadAndSave($file_path);

      $expanded[] = [
        'target_id' => $file->id(),
        'alt' => $record['alt'] ?? NULL,
        'title' => $record['title'] ?? NULL,
      ];
    }

    return count($expanded) === 1 ? $expanded[0] : $expanded;
  }

}
