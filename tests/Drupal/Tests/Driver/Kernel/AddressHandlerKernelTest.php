<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

/**
 * Kernel round-trip test for AddressHandler via the Core driver.
 *
 * Address is a multi-property field supplied by the 'drupal/address' contrib
 * module. The handler normalises input (scalar first name, numeric-indexed
 * array, or associative array) against the visible sub-field list configured
 * on the field. This test exercises the associative path.
 */
class AddressHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'address',
  ];

  /**
   * Tests round-trip for an address field with associative input.
   */
  public function testAddressAssociativeRoundTrip(): void {
    $this->attachField('field_address', 'address', [], [
      'available_countries' => ['US'],
      'field_overrides' => [],
      'langcode_override' => '',
    ]);

    $this->assertFieldRoundTripViaDriver('field_address', [
      [
        'given_name' => 'Jane',
        'family_name' => 'Doe',
        'address_line1' => '1 Infinite Loop',
        'locality' => 'Cupertino',
        'administrative_area' => 'CA',
        'postal_code' => '95014',
        'country_code' => 'US',
      ],
    ]);
  }

}
