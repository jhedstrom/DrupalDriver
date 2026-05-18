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
   * Accepts whatever shape the caller naturally has: a bare path, a list
   * of paths, a single record, or a list of records. 'normalise()' folds
   * all of those into a canonical list of records before iteration.
   * Returns a uniform list of records with 'target_id' resolved to a
   * File entity id.
   */
  public function expand($values): array {
    $records = $this->normalise($values);
    $expanded = [];

    foreach ($records as $record) {
      // normalise() already enforced that 'target_id' is a key on every
      // record; here we additionally reject NULL/empty values because the
      // file resolver and uploader need a real path/URI/basename.
      if ($record['target_id'] === NULL || $record['target_id'] === '') {
        throw new \InvalidArgumentException('Image field "target_id" must not be NULL or empty.');
      }

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
