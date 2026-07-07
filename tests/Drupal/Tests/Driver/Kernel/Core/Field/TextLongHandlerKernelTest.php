<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\filter\Entity\FilterFormat;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for TextLongHandler via the Core driver.
 *
 * Text_long is a multi-property field (value, format). The handler is a
 * pass-through, so this test verifies the payload reaches storage intact via
 * the driver's lookup + expand chain and comes back unchanged.
 *
 * @group fields
 */
#[Group('fields')]
class TextLongHandlerKernelTest extends FieldHandlerKernelTestBase {

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

    FilterFormat::create([
      'format' => 'plain_text',
      'name' => 'Plain text',
    ])->save();
  }

  /**
   * Tests round-trip for a text_long field with value and format properties.
   */
  public function testTextLongRoundTrip(): void {
    $this->attachField('field_description', 'text_long');

    $this->assertFieldRoundTripViaDriver('field_description', [
      [
        'value' => 'The quick brown fox.',
        'format' => 'plain_text',
      ],
    ]);
  }

}
