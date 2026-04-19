<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

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
    $this->attachAddressField();

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

  /**
   * Tests numeric-indexed input with country_code defaulted from field config.
   *
   * Exercises AddressHandler's positional-to-keyed normalisation and its
   * fallback where an omitted country_code falls back to the first entry in
   * the field's available_countries list.
   */
  public function testAddressNumericInputFallsBackToAvailableCountry(): void {
    $this->attachAddressField();

    $this->assertFieldRoundTripViaDriver('field_address', [
      [
        'Jane',
        NULL,
        'Doe',
        NULL,
        '1 Infinite Loop',
        NULL,
        '95014',
        NULL,
        'Cupertino',
        'CA',
      ],
    ]);
  }

  /**
   * Attaches the test address field with a single available country (US).
   */
  protected function attachAddressField(): void {
    $this->attachField('field_address', 'address', [], [
      'available_countries' => ['US'],
      'field_overrides' => [],
      'langcode_override' => '',
    ]);
  }

}
