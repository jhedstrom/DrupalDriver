<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Datetime field handler for Drupal 8.
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    // Fresh Drupal installs leave system.date:timezone.default NULL until the
    // installer writes a value; fall back to UTC so the handler never passes
    // NULL to DateTimeZone.
    $site_timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default') ?: 'UTC');
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

    foreach ($values as $key => $value) {
      $values[$key] = $this->formatDateValue($value, $site_timezone, $storage_timezone);
    }

    return $values;
  }

  /**
   * Formats a date value for storage based on the field's datetime_type.
   *
   * @param string|null $value
   *   The raw date value, optionally prefixed with "relative:".
   * @param \DateTimeZone $site_timezone
   *   The site timezone.
   * @param \DateTimeZone $storage_timezone
   *   The storage timezone.
   *
   * @return string|null
   *   The formatted date string, or null for empty values.
   */
  protected function formatDateValue(?string $value, \DateTimeZone $site_timezone, \DateTimeZone $storage_timezone): ?string {
    if ($value === NULL || $value === '') {
      return NULL;
    }

    // @codeCoverageIgnoreStart
    // The 'relative:' prefix is a convention used by consumers who feed the
    // handler values like 'relative:-1 day'; the kernel suite does not
    // exercise that shorthand.
    if (str_contains($value, 'relative:')) {
      $value = trim(str_replace('relative:', '', $value));
    }
    // @codeCoverageIgnoreEnd
    $is_date_only = $this->fieldInfo->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE;

    $date = new DrupalDateTime($value, $site_timezone);

    if ($is_date_only) {
      return $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }

    $date->setTimezone($storage_timezone);

    return $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
  }

}
