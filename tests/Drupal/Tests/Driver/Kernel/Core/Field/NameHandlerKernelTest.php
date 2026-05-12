<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\Driver\Core\Field\NameHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for NameHandler via the Core driver.
 *
 * Name is a multi-property field provided by the 'drupal/name' contrib
 * module. The handler accepts three input shapes (shorthand string,
 * numeric array, associative array) and normalises them into the same
 * per-component keyed structure. It also honours the field's
 * 'components' setting: positional input skips disabled components and
 * named input throws if it targets one.
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

  /**
   * Tests positional input maps into enabled components only.
   */
  public function testNamePositionalSkipsDisabledComponents(): void {
    $this->attachField('field_author', 'name', [], [
      'components' => [
        NameHandler::COMPONENT_TITLE => TRUE,
        NameHandler::COMPONENT_GIVEN => TRUE,
        NameHandler::COMPONENT_MIDDLE => FALSE,
        NameHandler::COMPONENT_FAMILY => TRUE,
        NameHandler::COMPONENT_GENERATIONAL => FALSE,
        NameHandler::COMPONENT_CREDENTIALS => FALSE,
      ],
    ]);

    $this->assertFieldRoundTripViaDriver('field_author', [
      ['Dr', 'Jane', 'Doe'],
    ]);
  }

}
