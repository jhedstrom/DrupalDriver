<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Entity\EntityStub;
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
   *
   * The input value is deliberately distinct from the handler's marker so
   * the assertion can only pass when the consumer handler actually ran. A
   * pass-through or built-in handler would leave the raw input in storage
   * and the comparison against 'MARKER' would fail.
   */
  public function testConsumerRegisteredHandlerWinsOverBuiltIn(): void {
    $this->core->registerFieldHandler('text_with_summary', MarkerTextWithSummaryHandler::class);
    $this->attachField('field_body', 'text_with_summary');

    $stub = new EntityStub(self::ENTITY_TYPE, self::BUNDLE, [
      'name' => 'test entity',
      'field_body' => [
        ['value' => 'raw input', 'format' => 'plain_text'],
      ],
    ]);

    $this->core->entityCreate($stub);

    $field_body = $stub->getValue('field_body');
    $this->assertSame(MarkerTextWithSummaryHandler::MARKER, $field_body[0]['value'], 'Consumer handler did not transform the field value during expand().');

    $reloaded = \Drupal::entityTypeManager()->getStorage(self::ENTITY_TYPE)->loadUnchanged($stub->getValue('id'));
    $this->assertInstanceOf(ContentEntityInterface::class, $reloaded);
    $this->assertSame(MarkerTextWithSummaryHandler::MARKER, $reloaded->get('field_body')->getValue()[0]['value'], 'Storage did not receive the consumer handler output.');
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

  public const MARKER = 'consumer handler took precedence';

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
