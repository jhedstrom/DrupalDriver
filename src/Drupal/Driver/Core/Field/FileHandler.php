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

      $file = $this->resolveExistingFile($file_path) ?? $this->uploadAndSave($file_path);

      $files[] = [
        'target_id' => $file->id(),
        'display' => $is_array ? ($value['display'] ?? 1) : 1,
        'description' => $is_array ? ($value['description'] ?? '') : '',
      ];
    }

    return $files;
  }

  /**
   * Returns a managed File addressed by URI or bare basename, or NULL.
   *
   * Restores the 2.x behaviour where tests could pre-create a managed file
   * and reference it by URI ('public://foo.txt') or bare basename
   * ('foo.txt') without triggering a re-upload. Paths containing '/' but no
   * scheme (e.g. '/tmp/foo.txt') are treated as disk paths and fall through
   * to the upload path unchanged.
   *
   * The native return type is 'object' (not FileInterface) so unit-test
   * doubles that satisfy the small 'id()' surface this method's callers
   * actually need can also pass without implementing the full File entity
   * contract. In production the storage returns File entities.
   *
   * @param string $value
   *   The raw field value: URI, bare basename, or absolute filesystem path.
   *
   * @return object|null
   *   A File entity (or File-compatible stub in tests), or NULL on no match.
   */
  protected function resolveExistingFile(string $value): ?object {
    $storage = \Drupal::entityTypeManager()->getStorage('file');

    if (str_contains($value, '://')) {
      $matches = $storage->loadByProperties(['uri' => $value]);

      return $matches ? reset($matches) : NULL;
    }

    if (!str_contains($value, '/')) {
      foreach (['public', 'private'] as $scheme) {
        $matches = $storage->loadByProperties(['uri' => $scheme . '://' . $value]);

        if ($matches) {
          return reset($matches);
        }
      }
    }

    return NULL;
  }

  /**
   * Reads a file from disk, writes it to public://, and returns the new File.
   *
   * Uses 'object' as the native return type for the same reason as
   * 'resolveExistingFile()': unit-test doubles can satisfy it without
   * implementing FileInterface. In production the repository service
   * returns a saved File entity.
   *
   * @param string $file_path
   *   A filesystem path readable from the current working directory.
   *
   * @return object
   *   A File entity (or File-compatible stub in tests).
   */
  protected function uploadAndSave(string $file_path): object {
    $data = file_get_contents($file_path);

    if ($data === FALSE) {
      throw new \Exception(sprintf('Error reading file %s.', $file_path));
    }

    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

    $file = \Drupal::service('file.repository')
      ->writeData($data, 'public://' . uniqid() . '.' . $file_extension);
    $file->save();

    return $file;
  }

}
