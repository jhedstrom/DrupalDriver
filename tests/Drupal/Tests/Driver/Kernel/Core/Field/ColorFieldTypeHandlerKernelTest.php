<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for ColorFieldTypeHandler via the Core driver.
 *
 * The 'color_field_type' field type (provided by drupal/color_field) stores
 * two columns ('color', 'opacity'), which DefaultHandler cannot marshal. The
 * kernel test's value is proving the driver resolves ColorFieldTypeHandler
 * for type 'color_field_type' and that the multi-column storage accepts what
 * the handler emits.
 *
 * @group fields
 */
#[Group('fields')]
class ColorFieldTypeHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'color_field',
  ];

  /**
   * Tests round-trip for a color field with a hex color and opacity.
   */
  public function testColorWithOpacityRoundTrip(): void {
    $this->attachField('field_color', 'color_field_type');

    // The input is already in the default '#HEXHEX' storage format, so the
    // field type's preSave() is a no-op and the stored color matches what
    // the handler emitted. Opacity recording is on by the field's default.
    $this->assertFieldRoundTripViaDriver('field_color', [
      ['color' => '#1A2B3C', 'opacity' => 0.5],
    ]);
  }

  /**
   * Tests round-trip for the bare-scalar color shorthand (opacity omitted).
   */
  public function testColorOnlyRoundTrip(): void {
    $this->attachField('field_swatch', 'color_field_type');

    $this->assertFieldRoundTripViaDriver('field_swatch', ['#1A2B3C']);
  }

}
