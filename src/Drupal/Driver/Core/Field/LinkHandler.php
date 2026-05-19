<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler for 'link' fields.
 */
class LinkHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  protected function normalise(mixed $values): array {
    if (!is_array($values)) {
      return [['uri' => $values]];
    }

    if ($values === []) {
      return [];
    }

    // Reject top-level mixed positional/named keys so a single-keyed
    // record cannot be confused with a list of positional ones.
    $has_int_key = FALSE;
    $has_string_key = FALSE;

    foreach (array_keys($values) as $key) {
      if (is_int($key)) {
        $has_int_key = TRUE;
      }
      else {
        $has_string_key = TRUE;
      }
    }

    if ($has_int_key && $has_string_key) {
      throw new \InvalidArgumentException(sprintf(
        'Link field value cannot mix positional and named keys at the top level. Got keys: %s.',
        implode(', ', array_keys($values)),
      ));
    }

    if (!array_is_list($values)) {
      $values = [$values];
    }

    $records = [];

    foreach ($values as $value) {
      if (!is_array($value)) {
        $records[] = ['uri' => $value];
        continue;
      }

      $record = [];

      if (isset($value['title']) || array_key_exists(0, $value)) {
        $record['title'] = $value['title'] ?? $value[0];
      }

      if (isset($value['uri']) || array_key_exists(1, $value)) {
        $record['uri'] = $value['uri'] ?? $value[1];
      }

      if (isset($value['options']) || array_key_exists(2, $value)) {
        $record['options'] = $value['options'] ?? $value[2];
      }

      if (!array_key_exists('uri', $record)) {
        throw new \InvalidArgumentException(sprintf(
          'Link field record must include a uri (named "uri" key or positional index 1). Got keys: %s.',
          implode(', ', array_keys($value)) ?: '(none)',
        ));
      }

      $records[] = $record;
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $links = [];

    foreach ($records as $record) {
      $link = array_filter([
        'title' => $record['title'] ?? NULL,
        'uri' => $record['uri'] ?? NULL,
        'options' => [],
      ], fn ($v): bool => $v !== NULL);

      // 'options' must be an array; UnroutedUrlAssembler::assemble()
      // rejects string values. Accept query-string shorthand and parse it.
      $options = $record['options'] ?? NULL;

      if (is_string($options) && $options !== '') {
        parse_str($options, $link['options']);
      }
      elseif (is_array($options)) {
        $link['options'] = $options;
      }

      $links[] = $link;
    }

    return $links;
  }

}
