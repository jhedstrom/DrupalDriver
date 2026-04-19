<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

/**
 * Kernel round-trip test for ListIntegerHandler via the Core driver.
 *
 * ListIntegerHandler inherits ListHandlerBase, so the label-to-key translation
 * behaviour mirrors ListStringHandler; the difference is storage stores an
 * integer, not a string. This test verifies the integer key round-trips.
 */
class ListIntegerHandlerKernelTest extends FieldHandlerKernelTestBase {

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
   * Tests that a label is translated to its integer key on round-trip.
   */
  public function testLabelToIntegerKeyRoundTrip(): void {
    $this->attachField('field_priority', 'list_integer', [
      'allowed_values' => [
        1 => 'Low',
        2 => 'Medium',
        3 => 'High',
      ],
    ]);

    // Pass the label; handler replaces with integer key 2.
    $this->assertFieldRoundTripViaDriver('field_priority', ['Medium']);
  }

}
