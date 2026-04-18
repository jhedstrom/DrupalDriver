<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

/**
 * Kernel round-trip test for ListFloatHandler via the Core driver.
 */
class ListFloatHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'options',
  ];

  /**
   * Tests that a label is translated to its float key on round-trip.
   */
  public function testLabelToFloatKeyRoundTrip(): void {
    $this->attachField('field_rating', 'list_float', [
      'allowed_values' => [
        '0.5' => 'Half',
        '1.0' => 'One',
        '1.5' => 'One and a half',
      ],
    ]);

    $this->assertFieldRoundTripViaDriver('field_rating', ['One']);
  }

}
