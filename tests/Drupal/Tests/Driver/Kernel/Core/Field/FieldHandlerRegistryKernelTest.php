<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\Driver\Core\Field\AbstractHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test asserting a consumer-registered handler wins end-to-end.
 *
 * The unit tests cover registry semantics in isolation; this test proves
 * that a class registered via 'Core::registerFieldHandler()' is actually
 * the one instantiated when 'entityCreate()' expands a field, by observing
 * the stored value differs from what the built-in handler would produce.
 *
 * @group core
 * @group fields
 */
#[Group('core')]
#[Group('fields')]
class FieldHandlerRegistryKernelTest extends FieldHandlerKernelTestBase {

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
    $this->installConfig(['filter']);
  }

  /**
   * Tests that a consumer-registered handler replaces the built-in.
   */
  public function testConsumerRegisteredHandlerWinsOverBuiltIn(): void {
    // Register an override for 'text_with_summary' that emits a known marker,
    // then drive a field of that type through entityCreate and verify the
    // marker lands in storage instead of what TextWithSummaryHandler would
    // produce.
    $this->core->registerFieldHandler('text_with_summary', MarkerTextWithSummaryHandler::class);
    $this->attachField('field_body', 'text_with_summary');

    $this->assertFieldRoundTripViaDriver('field_body', [
      ['value' => MarkerTextWithSummaryHandler::MARKER, 'format' => 'plain_text'],
    ]);
  }

}

/**
 * Test-only handler that emits a deterministic marker value.
 *
 * Extends 'AbstractHandler' directly so its class lineage does not touch
 * the built-in 'TextWithSummaryHandler' - this proves the registry is the
 * resolution path, not some hidden class-name convention.
 */
class MarkerTextWithSummaryHandler extends AbstractHandler {

  public const string MARKER = 'consumer handler took precedence';

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    // Replace each delta's 'value' with the marker while preserving other
    // columns. The kernel-test helper asserts the stored value equals what
    // the handler emitted, so the round-trip only passes if this handler
    // ran.
    $emitted = [];

    foreach ((array) $values as $delta) {
      $delta = is_array($delta) ? $delta : ['value' => $delta];
      $delta['value'] = self::MARKER;
      $emitted[] = $delta;
    }

    return $emitted;
  }

}
