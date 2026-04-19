<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Driver\Core\Field\AddressHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the AddressHandler field handler.
 */
class AddressHandlerTest extends TestCase {

  /**
   * Tests that a string value uses the first visible field.
   */
  public function testStringValueUsesFirstVisibleField(): void {
    $handler = $this->createHandler();

    $result = $handler->expand(['Just a name']);

    $this->assertSame([['given_name' => 'Just a name']], $result);
  }

  /**
   * Tests that keyed values are preserved and defaults filled in.
   */
  public function testKeyedValuesAreKeptAndDefaultCountryApplied(): void {
    $handler = $this->createHandler();

    $result = $handler->expand([
      [
        'given_name' => 'John',
        'family_name' => 'Doe',
      ],
    ]);

    $this->assertSame([
      [
        'given_name' => 'John',
        'family_name' => 'Doe',
        'country_code' => 'AU',
      ],
    ], $result);
  }

  /**
   * Tests that numeric indices are assigned in the order of visible fields.
   */
  public function testNumericIndicesMapToVisibleFieldOrder(): void {
    $handler = $this->createHandler();

    $result = $handler->expand([
      ['John', 'Doe'],
    ]);

    $this->assertSame([
      [
        'given_name' => 'John',
        'additional_name' => 'Doe',
        'country_code' => 'AU',
      ],
    ], $result);
  }

  /**
   * Tests that hidden fields are removed from the visible field list.
   */
  public function testHiddenFieldsAreSkippedForNumericIndices(): void {
    $handler = $this->createHandler([
      'givenName' => ['override' => 'hidden'],
      'additionalName' => ['override' => 'hidden'],
    ]);

    $result = $handler->expand([
      ['Doe'],
    ]);

    $this->assertSame([
      [
        'family_name' => 'Doe',
        'country_code' => 'AU',
      ],
    ], $result);
  }

  /**
   * Tests that non-hidden overrides do not alter the visible field list.
   */
  public function testNonHiddenOverridesAreIgnored(): void {
    $handler = $this->createHandler([
      'givenName' => ['override' => 'optional'],
    ]);

    $result = $handler->expand([
      ['John'],
    ]);

    $this->assertSame([
      [
        'given_name' => 'John',
        'country_code' => 'AU',
      ],
    ], $result);
  }

  /**
   * Tests that excess numeric indices trigger an exception.
   */
  public function testTooManyNumericIndicesThrows(): void {
    $handler = $this->createHandler([
      'additionalName' => ['override' => 'hidden'],
      'familyName' => ['override' => 'hidden'],
      'organization' => ['override' => 'hidden'],
      'addressLine1' => ['override' => 'hidden'],
      'addressLine2' => ['override' => 'hidden'],
      'postalCode' => ['override' => 'hidden'],
      'sortingCode' => ['override' => 'hidden'],
      'locality' => ['override' => 'hidden'],
      'administrativeArea' => ['override' => 'hidden'],
      'countryCode' => ['override' => 'hidden'],
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Too many address sub-field values supplied; only 1 visible fields available.');

    $handler->expand([
      ['John', 'Extra'],
    ]);
  }

  /**
   * Tests that a non-numeric, unknown sub-field key throws an exception.
   */
  public function testUnknownKeyThrows(): void {
    $handler = $this->createHandler();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid address sub-field key: unknown_key.');

    $handler->expand([
      ['unknown_key' => 'value'],
    ]);
  }

  /**
   * Tests that an explicit country_code is not overridden by the default.
   */
  public function testExplicitCountryCodeIsPreserved(): void {
    $handler = $this->createHandler();

    $result = $handler->expand([
      ['country_code' => 'US'],
    ]);

    $this->assertSame([['country_code' => 'US']], $result);
  }

  /**
   * Creates an AddressHandler with an injected fieldConfig mock.
   *
   * @param array<string, mixed> $field_overrides
   *   Address field override settings.
   * @param array<string, string> $available_countries
   *   Available countries keyed by code.
   *
   * @return \Drupal\Driver\Core\Field\AddressHandler
   *   Handler instance with fieldConfig populated.
   */
  protected function createHandler(array $field_overrides = [], array $available_countries = ['AU' => 'AU']): AddressHandler {
    $field_config = $this->createMock(FieldDefinitionInterface::class);
    $field_config->method('getSettings')->willReturn([
      'field_overrides' => $field_overrides,
      'available_countries' => $available_countries,
    ]);

    $reflection = new \ReflectionClass(AddressHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $property = new \ReflectionProperty(AddressHandler::class, 'fieldConfig');
    $property->setValue($handler, $field_config);

    return $handler;
  }

}
