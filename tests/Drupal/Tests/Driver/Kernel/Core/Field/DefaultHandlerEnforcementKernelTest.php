<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\Driver\Core\Core;
use Drupal\Driver\Entity\EntityStub;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test: Core rejects a field the DefaultHandler fallback cannot marshal.
 *
 * When no dedicated handler is registered for a field type, Core consults the
 * field classifier before falling back to DefaultHandler. A field whose stored
 * shape the default cannot relay - here an entity-reference target - is refused
 * at handler resolution with an actionable message, rather than silently
 * passing a label through as a bogus id.
 *
 * @group core
 * @group fields
 */
#[Group('core')]
#[Group('fields')]
class DefaultHandlerEnforcementKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = self::BASE_MODULES;

  /**
   * Tests that an unhandled entity-reference field is refused, not relayed.
   */
  public function testEntityReferenceFieldWithoutHandlerIsRejected(): void {
    // A Core that registers no field handlers, forcing every field to the
    // DefaultHandler fallback so the classifier gate in getFieldHandler() runs.
    $core = new class($this->root) extends Core {

      /**
       * {@inheritdoc}
       */
      protected function registerDefaultFieldHandlers(): void {
      }

    };

    $this->attachField('field_ref', 'entity_reference', ['target_type' => 'user']);

    $stub = new EntityStub(self::ENTITY_TYPE, self::BUNDLE, [
      'name' => 'test entity',
      'field_ref' => ['Anonymous'],
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/No dedicated handler is registered.*entity-reference/s');

    $core->entityCreate($stub);
  }

}
