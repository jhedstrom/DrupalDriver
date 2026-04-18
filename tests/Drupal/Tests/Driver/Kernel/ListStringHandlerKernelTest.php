<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

/**
 * Kernel round-trip test for list_string fields via the Core driver.
 *
 * list_string is single-property but its handler translates human-readable
 * labels to the machine keys declared in the field's allowed_values storage
 * setting. This test exercises that translation end-to-end: the driver
 * receives a label, the handler swaps it for the key, storage accepts the
 * key, and the round-trip returns the key unchanged.
 */
class ListStringHandlerKernelTest extends FieldHandlerKernelTestBase {

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
   * Tests that a label is translated to its allowed_values key on round-trip.
   */
  public function testLabelToKeyRoundTrip(): void {
    $this->attachField('field_status', 'list_string', [
      'allowed_values' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
      ],
    ]);

    // Pass the label; the handler replaces it with 'active' (the key).
    // After the driver mutates the stub, the assertion sees the key and
    // compares it against what storage returned.
    $this->assertFieldRoundTripViaDriver('field_status', ['Active']);
  }

  /**
   * Tests that a value already equal to an allowed key round-trips as-is.
   */
  public function testKeyPassesThroughRoundTrip(): void {
    $this->attachField('field_status', 'list_string', [
      'allowed_values' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
      ],
    ]);

    // 'inactive' is not a label, so the handler leaves it untouched and the
    // key reaches storage directly.
    $this->assertFieldRoundTripViaDriver('field_status', ['inactive']);
  }

}
