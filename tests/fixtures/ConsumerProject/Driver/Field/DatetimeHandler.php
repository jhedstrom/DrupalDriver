<?php

declare(strict_types=1);

namespace ConsumerProject\Driver\Field;

use Drupal\Driver\Core\Field\AbstractHandler;

/**
 * Fixture: consumer-side override for the 'datetime' handler.
 *
 * The library ships its own 'DatetimeHandler' in 'Drupal\Driver\Core\Field';
 * this one shares the basename but lives in the consumer's namespace. When
 * 'ConsumerCore::registerDefaultFieldHandlers()' calls the parent first and
 * then re-scans its own 'Field/' directory, this registration wins because
 * 'registerFieldHandler()' is last-write-wins on the field type key. Choosing
 * a field type the library already handles is the point: it proves the
 * consumer scan overrides a built-in, not merely fills a gap.
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * Marker value the test asserts appears in storage.
   *
   * A valid ISO 8601 datetime storage string, distinct from the input date,
   * so a stored match can only mean this handler ran instead of the library's
   * date-parsing one.
   */
  public const MARKER = '2001-09-11T08:46:00';

  /**
   * {@inheritdoc}
   */
  protected function doExpand(array $records): array {
    $emitted = [];

    foreach ($records as $record) {
      $record['value'] = self::MARKER;
      $emitted[] = $record;
    }

    return $emitted;
  }

}
