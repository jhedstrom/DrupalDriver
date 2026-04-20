<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for BooleanHandler via the Core driver.
 *
 * Asserts that scenarios can populate boolean fields with human-readable
 * words ('Yes', 'Published') instead of 1/0, and that unrecognised values
 * raise a clear error rather than silently coercing to FALSE.
 *
 * @group fields
 */
#[Group('fields')]
class BooleanHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
  ];

  /**
   * Tests canonical 'Yes' resolves to 1 and round-trips via storage.
   */
  public function testCanonicalYesRoundTrip(): void {
    $this->attachField('field_flag', 'boolean');
    $this->assertFieldRoundTripViaDriver('field_flag', ['Yes']);
  }

  /**
   * Tests canonical 'no' resolves to 0 and round-trips via storage.
   */
  public function testCanonicalNoRoundTrip(): void {
    $this->attachField('field_flag', 'boolean');
    $this->assertFieldRoundTripViaDriver('field_flag', ['no']);
  }

  /**
   * Tests the field's configured on_label takes priority over canonical forms.
   *
   * Site builders often customise the labels (e.g. 'Published'/'Draft' on a
   * publishing workflow field). Scenarios should be able to use those exact
   * words without the handler second-guessing them.
   */
  public function testFieldOnLabelResolvesToTrue(): void {
    $this->attachField('field_flag', 'boolean', [], [
      'on_label' => 'Published',
      'off_label' => 'Draft',
    ]);

    $this->assertFieldRoundTripViaDriver('field_flag', ['Published']);
  }

  /**
   * Tests the field's configured off_label resolves to 0.
   */
  public function testFieldOffLabelResolvesToFalse(): void {
    $this->attachField('field_flag', 'boolean', [], [
      'on_label' => 'Published',
      'off_label' => 'Draft',
    ]);

    $this->assertFieldRoundTripViaDriver('field_flag', ['Draft']);
  }

  /**
   * Tests an unrecognised value raises a descriptive exception.
   */
  public function testUnrecognisedValueThrows(): void {
    $this->attachField('field_flag', 'boolean');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/Cannot convert "maybe" to a boolean/');

    $this->assertFieldRoundTripViaDriver('field_flag', ['maybe']);
  }

}
