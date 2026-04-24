<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\filter\Entity\FilterFormat;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for TextHandler via the Core driver.
 *
 * Text is multi-property (value, format). The handler is a pass-through
 * so this test verifies the payload reaches storage intact and is
 * re-hydrated unchanged when the entity is reloaded.
 *
 * @group fields
 */
#[Group('fields')]
class TextHandlerKernelTest extends FieldHandlerKernelTestBase {

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
   * Tests round-trip for a text field with value and format properties.
   */
  public function testTextRoundTrip(): void {
    $this->attachField('field_subtitle', 'text');

    $this->assertFieldRoundTripViaDriver('field_subtitle', [
      [
        'value' => 'Short label.',
        'format' => 'plain_text',
      ],
    ]);
  }

}
