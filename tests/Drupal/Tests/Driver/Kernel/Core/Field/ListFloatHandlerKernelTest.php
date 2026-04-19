<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for ListFloatHandler via the Core driver.
 *
 * @group fields
 */
#[Group('fields')]
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
    // Use fractional-only keys so the stored value actually exercises float
    // handling; keys like '1.0' normalise to integer '1' on storage and would
    // not distinguish list_float from list_integer.
    $this->attachField('field_rating', 'list_float', [
      'allowed_values' => [
        '0.5' => 'Half',
        '1.5' => 'One and a half',
        '2.5' => 'Two and a half',
      ],
    ]);

    $this->assertFieldRoundTripViaDriver('field_rating', ['Half']);
  }

}
