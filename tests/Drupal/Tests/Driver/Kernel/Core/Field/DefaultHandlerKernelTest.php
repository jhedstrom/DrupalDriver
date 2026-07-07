<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\filter\Entity\FilterFormat;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip tests for DefaultHandler via the Core driver.
 *
 * DefaultHandler is the fallback for any field type without a dedicated
 * handler class. It relays a field whose stored columns are all plain scalars
 * straight to storage, so single-column scalars ('string') and multi-column
 * scalar fields ('text', 'text_long', 'text_with_summary', 'color_field_type')
 * all resolve to it and round-trip through real storage unchanged.
 *
 * @group fields
 */
#[Group('fields')]
class DefaultHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'text',
    'filter',
    'color_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'plain_text',
      'name' => 'Plain text',
    ])->save();
  }

  /**
   * Tests round-trip for a string field (single-column scalar).
   */
  public function testStringRoundTripViaDefaultHandler(): void {
    $this->attachField('field_note', 'string');

    $this->assertFieldRoundTripViaDriver('field_note', ['hello world']);
  }

  /**
   * Tests round-trip for a text field (value + format scalar columns).
   */
  public function testTextRoundTripViaDefaultHandler(): void {
    $this->attachField('field_subtitle', 'text');

    $this->assertFieldRoundTripViaDriver('field_subtitle', [
      [
        'value' => 'Short label.',
        'format' => 'plain_text',
      ],
    ]);
  }

  /**
   * Tests round-trip for a text_long field (value + format scalar columns).
   */
  public function testTextLongRoundTripViaDefaultHandler(): void {
    $this->attachField('field_description', 'text_long');

    $this->assertFieldRoundTripViaDriver('field_description', [
      [
        'value' => 'The quick brown fox.',
        'format' => 'plain_text',
      ],
    ]);
  }

  /**
   * Tests round-trip for a text_with_summary field (all scalar columns).
   */
  public function testTextWithSummaryRoundTripViaDefaultHandler(): void {
    $this->attachField('field_body', 'text_with_summary');

    $this->assertFieldRoundTripViaDriver('field_body', [
      [
        'value' => 'The quick brown fox.',
        'summary' => 'A summary.',
        'format' => 'plain_text',
      ],
    ]);
  }

  /**
   * Tests round-trip for a color field with a hex color and opacity.
   */
  public function testColorWithOpacityRoundTripViaDefaultHandler(): void {
    $this->attachField('field_color', 'color_field_type');

    $this->assertFieldRoundTripViaDriver('field_color', [
      ['color' => '#1A2B3C', 'opacity' => 0.5],
    ]);
  }

  /**
   * Tests round-trip for the bare-scalar color shorthand (opacity omitted).
   */
  public function testColorOnlyRoundTripViaDefaultHandler(): void {
    $this->attachField('field_swatch', 'color_field_type');

    $this->assertFieldRoundTripViaDriver('field_swatch', ['#1A2B3C']);
  }

}
