<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use ConsumerProject\Driver\ConsumerCore;
use ConsumerProject\Driver\Field\StringLongHandler as ConsumerStringLongHandler;
use ConsumerProject\Driver\Field\TextLongHandler as ConsumerTextLongHandler;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Driver\Entity\EntityStub;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for consumer-supplied Core and its bundled field handlers.
 *
 * Exercises the two extension seams advertised in the README against a full
 * Drupal kernel. The test replaces 'Core' with 'ConsumerCore' - a fixture
 * living outside the 'Drupal\Driver' namespace - and proves that its
 * 'Field/' directory scan contributes handlers that actually run during
 * 'entityCreate':
 *
 *  - An override: 'ConsumerProject\Driver\Field\TextLongHandler' shadows the
 *    library's 'Drupal\Driver\Core\Field\TextLongHandler' for the
 *    'text_long' field type.
 *  - A new registration: 'ConsumerProject\Driver\Field\StringLongHandler'
 *    registers a handler for 'string_long', a field type the library does
 *    not cover itself.
 *
 * @group core
 * @group fields
 */
#[Group('core')]
#[Group('fields')]
class CustomCoreKernelTest extends FieldHandlerKernelTestBase {

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
   *
   * Replaces the library's 'Core' with 'ConsumerCore' so the handlers under
   * test come from the fixture's directory scan, not the library's.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['filter']);
    $this->core = new ConsumerCore($this->root);
  }

  /**
   * Tests that the consumer override replaces the library's 'text_long'.
   *
   * Input differs from the handler's marker so the assertion only passes
   * when the consumer handler actually ran - a pass-through handler would
   * leave the raw input in storage and fail the comparison.
   */
  public function testConsumerCoreOverridesLibraryHandler(): void {
    $this->attachField('field_body', 'text_long');

    $stub = new EntityStub(self::ENTITY_TYPE, self::BUNDLE, [
      'name' => 'test entity',
      'field_body' => [
        ['value' => 'raw input', 'format' => 'plain_text'],
      ],
    ]);

    $this->core->entityCreate($stub);

    $field_body = $stub->getValue('field_body');
    $this->assertSame(ConsumerTextLongHandler::MARKER, $field_body[0]['value'], 'Consumer handler did not transform the field value during expand().');

    $reloaded = \Drupal::entityTypeManager()->getStorage(self::ENTITY_TYPE)->loadUnchanged($stub->getValue('id'));
    $this->assertInstanceOf(ContentEntityInterface::class, $reloaded);
    $this->assertSame(ConsumerTextLongHandler::MARKER, $reloaded->get('field_body')->getValue()[0]['value'], 'Storage did not receive the consumer handler output.');
  }

  /**
   * Tests that the consumer Core registers handlers for new field types.
   *
   * 'string_long' is a Drupal-core field type; the library does not ship a
   * dedicated handler for it. Without 'ConsumerCore', the lookup would fall
   * through to 'DefaultHandler' and store the raw input verbatim. The
   * fixture adds a handler that rewrites 'value', and this test proves the
   * rewritten value is what lands in storage.
   */
  public function testConsumerCoreAddsHandlerForNewFieldType(): void {
    $this->attachField('field_summary', 'string_long');

    $stub = new EntityStub(self::ENTITY_TYPE, self::BUNDLE, [
      'name' => 'test entity',
      'field_summary' => [['value' => 'raw input']],
    ]);

    $this->core->entityCreate($stub);

    $field_summary = $stub->getValue('field_summary');
    $this->assertSame(ConsumerStringLongHandler::MARKER, $field_summary[0]['value'], 'Consumer handler did not transform the field value during expand().');

    $reloaded = \Drupal::entityTypeManager()->getStorage(self::ENTITY_TYPE)->loadUnchanged($stub->getValue('id'));
    $this->assertInstanceOf(ContentEntityInterface::class, $reloaded);
    $this->assertSame(ConsumerStringLongHandler::MARKER, $reloaded->get('field_summary')->getValue()[0]['value'], 'Storage did not receive the consumer handler output.');
  }

}
