<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Driver\Core\Field\AddressHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the AddressHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class AddressHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    return $this->createHandlerWithSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'bare string maps to first visible field plus country fallback' => [
      'Just a name',
      [['given_name' => 'Just a name', 'country_code' => 'AU']],
      NULL,
      NULL,
    ];
    yield 'keyed record auto-wrapped with country backfill' => [
      ['given_name' => 'John', 'family_name' => 'Doe'],
      [['given_name' => 'John', 'family_name' => 'Doe', 'country_code' => 'AU']],
      NULL,
      NULL,
    ];
    yield 'list with keyed record' => [
      [['given_name' => 'John', 'family_name' => 'Doe']],
      [['given_name' => 'John', 'family_name' => 'Doe', 'country_code' => 'AU']],
      NULL,
      NULL,
    ];
    yield 'positional indices fill visible fields in order' => [
      [['John', 'Doe']],
      [['given_name' => 'John', 'additional_name' => 'Doe', 'country_code' => 'AU']],
      NULL,
      NULL,
    ];
    yield 'explicit country_code preserved' => [
      [['country_code' => 'US']],
      [['country_code' => 'US']],
      NULL,
      NULL,
    ];
    yield 'multi-delta backfills each record' => [
      [
        ['given_name' => 'John'],
        ['given_name' => 'Jane', 'country_code' => 'US'],
      ],
      [
        ['given_name' => 'John', 'country_code' => 'AU'],
        ['given_name' => 'Jane', 'country_code' => 'US'],
      ],
      NULL,
      NULL,
    ];

    yield 'unknown sub-field key rejected' => [
      [['unknown_key' => 'value']],
      NULL,
      \RuntimeException::class,
      'Invalid address sub-field key: unknown_key.',
    ];
  }

  /**
   * Tests that hidden fields are removed from the visible field list.
   */
  public function testHiddenFieldsAreSkippedForNumericIndices(): void {
    $handler = $this->createHandlerWithSettings([
      'givenName' => ['override' => 'hidden'],
      'additionalName' => ['override' => 'hidden'],
    ]);

    $this->assertSame(
      [['family_name' => 'Doe', 'country_code' => 'AU']],
      $handler->expand([['Doe']]),
    );
  }

  /**
   * Tests that non-hidden overrides do not alter the visible field list.
   */
  public function testNonHiddenOverridesAreIgnored(): void {
    $handler = $this->createHandlerWithSettings([
      'givenName' => ['override' => 'optional'],
    ]);

    $this->assertSame(
      [['given_name' => 'John', 'country_code' => 'AU']],
      $handler->expand([['John']]),
    );
  }

  /**
   * Tests that excess numeric indices trigger an exception.
   */
  public function testTooManyNumericIndicesThrows(): void {
    $handler = $this->createHandlerWithSettings([
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

    $handler->expand([['John', 'Extra']]);
  }

  /**
   * Creates an AddressHandler with an injected fieldConfig mock.
   *
   * @param array<string, mixed> $field_overrides
   *   Address field override settings.
   * @param array<string, string> $available_countries
   *   Available countries keyed by code.
   */
  protected function createHandlerWithSettings(array $field_overrides = [], array $available_countries = ['AU' => 'AU']): AddressHandler {
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
