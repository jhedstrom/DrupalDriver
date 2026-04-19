<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Image field handler for Drupal 8.
 */
class ImageHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $file_path = $values[0];
    $file_contents = file_get_contents($file_path);

    if ($file_contents === FALSE) {
      throw new \Exception(sprintf('Error reading file %s.', $file_path));
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = \Drupal::service('file.repository')
      ->writeData($file_contents, 'public://' . uniqid() . '.jpg');

    // @codeCoverageIgnoreStart
    // 'file.repository::writeData' returns a File entity or throws;
    // retained here as a defensive guard.
    if ($file === FALSE) {
      throw new \Exception('Error saving file.');
    }
    // @codeCoverageIgnoreEnd
    $file->save();

    return [
      'target_id' => $file->id(),
      'alt' => $values['alt'] ?? NULL,
      'title' => $values['title'] ?? NULL,
    ];
  }

}
