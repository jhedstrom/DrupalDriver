<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Name field handler for Drupal 8.
 *
 * Supports the Name module (https://www.drupal.org/project/name).
 */
class NameHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $components = ['title', 'given', 'middle', 'family', 'generational', 'credentials'];
    $names = [];

    foreach ($values as $value) {
      if (is_string($value)) {
        // Support "Family, Given" shorthand.
        $parts = array_map(trim(...), explode(',', $value));
        $names[] = [
          'family' => $parts[0] ?? NULL,
          'given' => $parts[1] ?? NULL,
        ];
        continue;
      }

      if (is_array($value)) {
        $names[] = $this->normaliseComponents($value, $components);
      }
    }

    return $names;
  }

  /**
   * Normalises a name value array into a keyed components array.
   *
   * @param array<int|string, mixed> $value
   *   The raw name value. Keys may be component names (title, given, family,
   *   ...) or numeric indices mapping into the component order.
   * @param array<int, string> $components
   *   The ordered list of recognised name component keys.
   *
   * @return array<string, mixed>
   *   A keyed array of name components.
   */
  protected function normaliseComponents(array $value, array $components): array {
    $name = [];
    $position = 0;

    foreach ($value as $key => $field_value) {
      if (in_array($key, $components, TRUE)) {
        $name[$key] = $field_value;
        continue;
      }

      if (is_numeric($key) && isset($components[$position])) {
        $name[$components[$position]] = $field_value;
        $position++;
      }
    }

    return $name;
  }

}
