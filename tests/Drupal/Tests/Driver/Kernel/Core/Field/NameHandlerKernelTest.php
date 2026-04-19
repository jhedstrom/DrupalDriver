<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for NameHandler via the Core driver.
 *
 * Name is a multi-property field provided by the 'drupal/name' contrib
 * module. The handler accepts three input shapes (shorthand string,
 * numeric array, associative array) and normalises them into the same
 * per-component keyed structure.
 *
 * @group fields
 */
#[Group('fields')]
class NameHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'name',
  ];

  /**
   * Tests round-trip for a name field with associative input.
   */
  public function testNameAssociativeRoundTrip(): void {
    $this->attachField('field_author', 'name');

    $this->assertFieldRoundTripViaDriver('field_author', [
      [
        'given' => 'Jane',
        'family' => 'Doe',
      ],
    ]);
  }

  /**
   * Tests round-trip for a name field with "Family, Given" shorthand.
   */
  public function testNameShorthandStringRoundTrip(): void {
    $this->attachField('field_author', 'name');

    $this->assertFieldRoundTripViaDriver('field_author', ['Doe, Jane']);
  }

}
