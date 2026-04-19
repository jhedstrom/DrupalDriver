<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for DefaultHandler via the Core driver.
 *
 * DefaultHandler is the fallback used for any field type without a dedicated
 * handler class. This test verifies the fallback resolves correctly and that
 * the passthrough output round-trips through real storage. The 'string' field
 * type has no DrupalDriver handler, so the lookup chain lands on
 * DefaultHandler.
 */
#[Group('fields')]
class DefaultHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = self::BASE_MODULES;

  /**
   * Tests round-trip for a string field (no specific handler defined).
   */
  public function testStringRoundTripViaDefaultHandler(): void {
    $this->attachField('field_note', 'string');

    $this->assertFieldRoundTripViaDriver('field_note', ['hello world']);
  }

}
