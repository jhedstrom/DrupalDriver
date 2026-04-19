<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\filter\Entity\FilterFormat;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for TextWithSummaryHandler via the Core driver.
 *
 * The text_with_summary field is multi-property (value, summary, format). The
 * handler is a passthrough, so this test verifies the multi-property payload
 * reaches storage intact via the driver's lookup + expand chain.
 */
#[Group('fields')]
class TextWithSummaryHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'text',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // text_with_summary requires a defined filter format for the 'format' key
    // to be accepted by validation-friendly storage paths.
    FilterFormat::create([
      'format' => 'plain_text',
      'name' => 'Plain text',
    ])->save();
  }

  /**
   * Tests round-trip for a text_with_summary field with all three properties.
   */
  public function testTextWithSummaryRoundTrip(): void {
    $this->attachField('field_body', 'text_with_summary');

    $this->assertFieldRoundTripViaDriver('field_body', [
      [
        'value' => 'The quick brown fox.',
        'summary' => 'A summary.',
        'format' => 'plain_text',
      ],
    ]);
  }

}
