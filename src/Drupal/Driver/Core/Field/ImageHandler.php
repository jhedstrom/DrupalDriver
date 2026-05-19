<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'image' fields.
 */
class ImageHandler extends FileHandler {

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $expanded = [];

    foreach ($records as $record) {
      $file_path = $record[$this->mainProperty];
      $file = $this->resolveExistingFile($file_path) ?? $this->uploadAndSave($file_path);

      $expanded[] = [
        $this->mainProperty => $file->id(),
        'alt' => $record['alt'] ?? NULL,
        'title' => $record['title'] ?? NULL,
      ];
    }

    return $expanded;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldLabel(): string {
    return 'Image';
  }

}
