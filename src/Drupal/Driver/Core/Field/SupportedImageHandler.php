<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'supported_image' fields (supported_image contrib module).
 *
 * @see https://www.drupal.org/project/supported_image
 */
class SupportedImageHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function normalise(mixed $values): array {
    $records = parent::normalise($values);

    foreach ($records as &$record) {
      if ($record[$this->mainProperty] === NULL || $record[$this->mainProperty] === '') {
        throw new \InvalidArgumentException(sprintf('Supported image field "%s" must not be NULL or empty.', $this->mainProperty));
      }

      $record[$this->mainProperty] = (string) $record[$this->mainProperty];
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $images = [];

    foreach ($records as $record) {
      $file_path = $record[$this->mainProperty];
      $file_extension = pathinfo((string) $file_path, PATHINFO_EXTENSION);
      $data = file_get_contents($file_path);

      if ($data === FALSE) {
        throw new \Exception(sprintf('Error reading file %s.', $file_path));
      }

      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::service('file.repository')
        ->writeData($data, 'public://' . uniqid() . '.' . $file_extension);
      $file->save();

      $images[] = [
        $this->mainProperty => $file->id(),
        'alt' => $record['alt'] ?? NULL,
        'title' => $record['title'] ?? NULL,
        'caption_value' => $record['caption_value'] ?? NULL,
        'caption_format' => $record['caption_format'] ?? NULL,
        'attribution_value' => $record['attribution_value'] ?? NULL,
        'attribution_format' => $record['attribution_format'] ?? NULL,
      ];
    }

    return $images;
  }

}
