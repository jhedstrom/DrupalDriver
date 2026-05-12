<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Name field handler for Drupal 8.
 *
 * Supports the Name module (https://www.drupal.org/project/name).
 */
class NameHandler extends AbstractHandler {

  public const COMPONENT_TITLE = 'title';
  public const COMPONENT_GIVEN = 'given';
  public const COMPONENT_MIDDLE = 'middle';
  public const COMPONENT_FAMILY = 'family';
  public const COMPONENT_GENERATIONAL = 'generational';
  public const COMPONENT_CREDENTIALS = 'credentials';

  /**
   * Canonical order of name components.
   *
   * Matches the columns declared by 'NameItem::schema()' and the order in
   * which the Name module presents components in the field UI.
   *
   * @var array<int, string>
   */
  protected const COMPONENTS = [
    self::COMPONENT_TITLE,
    self::COMPONENT_GIVEN,
    self::COMPONENT_MIDDLE,
    self::COMPONENT_FAMILY,
    self::COMPONENT_GENERATIONAL,
    self::COMPONENT_CREDENTIALS,
  ];

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    $enabled = $this->getEnabledComponents();
    $names = [];

    foreach ($values as $value) {
      if (is_string($value)) {
        $names[] = $this->normaliseString($value, $enabled);
        continue;
      }

      if (is_array($value)) {
        $names[] = $this->normaliseArray($value, $enabled);
      }
    }

    return $names;
  }

  /**
   * Returns name components enabled on the field, in canonical order.
   *
   * @return array<int, string>
   *   Enabled component keys (subset of self::COMPONENTS).
   */
  protected function getEnabledComponents(): array {
    $components = $this->fieldConfig->getSettings()['components'] ?? [];
    $enabled = [];

    foreach (self::COMPONENTS as $component) {
      if (!empty($components[$component])) {
        $enabled[] = $component;
      }
    }

    return $enabled;
  }

  /**
   * Expands the "Family, Given" shorthand string.
   *
   * @param string $value
   *   The shorthand value: either "Family" or "Family, Given".
   * @param array<int, string> $enabled
   *   Enabled component keys.
   *
   * @return array<string, mixed>
   *   The keyed component array.
   */
  protected function normaliseString(string $value, array $enabled): array {
    $parts = array_map(trim(...), explode(',', $value));

    if (!in_array(self::COMPONENT_FAMILY, $enabled, TRUE)) {
      throw new \RuntimeException('Cannot use the "Family, Given" shorthand because the "family" component is disabled on this field.');
    }

    $name = [self::COMPONENT_FAMILY => $parts[0]];
    $has_given_part = isset($parts[1]);
    $given_enabled = in_array(self::COMPONENT_GIVEN, $enabled, TRUE);

    if ($has_given_part && !$given_enabled) {
      throw new \RuntimeException('Cannot use the "Family, Given" shorthand because the "given" component is disabled on this field.');
    }

    if ($given_enabled) {
      $name[self::COMPONENT_GIVEN] = $parts[1] ?? NULL;
    }

    return $name;
  }

  /**
   * Expands an associative or positional array into a keyed component array.
   *
   * @param array<int|string, mixed> $value
   *   The raw name value.
   * @param array<int, string> $enabled
   *   Enabled component keys, in canonical order.
   *
   * @return array<string, mixed>
   *   A keyed array of name components.
   */
  protected function normaliseArray(array $value, array $enabled): array {
    if ($value !== [] && !array_is_list($value) && $this->hasNumericKey($value)) {
      throw new \RuntimeException('Cannot mix numeric and named keys in the same name value; use one shape consistently.');
    }

    $name = [];
    $position = 0;

    foreach ($value as $key => $field_value) {
      if (is_numeric($key)) {
        if (!isset($enabled[$position])) {
          throw new \RuntimeException(sprintf('Too many name sub-field values supplied; only %d enabled components available.', count($enabled)));
        }

        $name[$enabled[$position]] = $field_value;
        $position++;
        continue;
      }

      if (in_array($key, $enabled, TRUE)) {
        $name[$key] = $field_value;
        continue;
      }

      if (in_array($key, self::COMPONENTS, TRUE)) {
        throw new \RuntimeException(sprintf('Cannot set the "%s" name component because it is disabled on this field.', $key));
      }

      throw new \RuntimeException(sprintf('Invalid name sub-field key: %s.', $key));
    }

    return $name;
  }

  /**
   * Returns TRUE if any key in the array is numeric.
   *
   * @param array<int|string, mixed> $value
   *   The array to check.
   */
  protected function hasNumericKey(array $value): bool {
    foreach (array_keys($value) as $key) {
      if (is_numeric($key)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
