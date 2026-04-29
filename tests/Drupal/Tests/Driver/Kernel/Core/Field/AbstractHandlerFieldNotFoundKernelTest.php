<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\Driver\Core\Field\DefaultHandler;
use Drupal\Driver\Entity\EntityStub;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for AbstractHandler's field-not-found guard.
 *
 * 'Core::getFieldHandler()' already validates field existence before
 * instantiating a handler, so the guard is only reachable when a caller
 * constructs a handler directly (e.g. via custom Core subclasses). This test
 * exercises that direct-construction path against a real entity_field.manager
 * service.
 *
 * @group fields
 */
#[Group('fields')]
class AbstractHandlerFieldNotFoundKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = self::BASE_MODULES;

  /**
   * Tests that the constructor throws when the requested field does not exist.
   */
  public function testConstructorThrowsOnUnknownField(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/does not exist on entity type "entity_test"/');

    new DefaultHandler(new EntityStub(self::ENTITY_TYPE), self::ENTITY_TYPE, 'field_does_not_exist');
  }

}
