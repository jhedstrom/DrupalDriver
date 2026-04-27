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
      $raw = is_array($value) ? $value['target_id'] ?? $value[0] : $value;
      $display = is_array($value) ? ($value['display'] ?? 1) : 1;
      $description = is_array($value) ? ($value['description'] ?? '') : '';

      $target_id = $this->resolveTargetId($raw);

      $return[] = [
        'target_id' => $target_id,
        'display' => $display,
        'description' => $description,
      ];
    }

    return $return;
  }

  /**
   * Resolves a raw value to a managed file id.
   *
   * Tries the filesystem first so callers can import a brand-new file by path
   * (v2.5.0 behavior). Falls back to looking up an existing managed file by
   * numeric id or filename (v2.4.3 behavior) so suites that pre-create files
   * via 'managed files' steps keep working.
   */
  protected function resolveTargetId($value) {
    if (is_string($value) && is_file($value)) {
      return $this->createFileFromPath($value);
    }

    $existing = $this->loadExistingManagedFile($value);
    if ($existing !== NULL) {
      return $existing->id();
    }

    throw new \Exception(sprintf('Could not resolve file value "%s" as a path on disk or an existing managed file.', is_scalar($value) ? $value : gettype($value)));
  }

  /**
   * Reads a file from disk and creates a new managed file entity.
   */
  protected function createFileFromPath($path) {
    $data = file_get_contents($path);

    if ($data === FALSE) {
      throw new \Exception(sprintf('Error reading file %s.', $path));
    }

    $extension = pathinfo($path, PATHINFO_EXTENSION);

    /** @var \Drupal\file\FileInterface $file */
    $file = \Drupal::service('file.repository')
      ->writeData($data, 'public://' . uniqid() . '.' . $extension);

    if ($file === FALSE) {
      throw new \Exception('Error saving file.');
    }

    $file->save();

    return $file->id();
  }

  /**
   * Loads an existing managed file by numeric id or filename.
   *
   * @return \Drupal\file\FileInterface|null
   *   The matching file entity, or NULL when no match exists.
   */
  protected function loadExistingManagedFile($value) {
    if (!is_scalar($value)) {
      return NULL;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('file');

    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
      $file = $storage->load((int) $value);
      if ($file) {
        return $file;
      }
    }

    $files = $storage->loadByProperties(['filename' => $value]);

    return $files ? reset($files) : NULL;
  }

}
