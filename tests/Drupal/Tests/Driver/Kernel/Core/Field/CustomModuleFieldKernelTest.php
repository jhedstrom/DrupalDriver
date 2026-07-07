<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\Driver\Entity\EntityStub;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test: a contrib module's custom field types through the real driver.
 *
 * The 'driver_field_test' fixture module ships two field types the driver has
 * no handler for, standing in for any contrib module that introduces its own
 * field type. Both cases run against the real 'Core' with every built-in
 * handler registered - not a stripped-down subclass - so this proves the
 * classifier gate end to end:
 *
 *  - 'driver_test_scalar' (plain-scalar columns) rides 'DefaultHandler' and
 *    round-trips through real storage intact.
 *  - 'driver_test_reference' (an entity-reference target column) is refused at
 *    handler resolution with the actionable "register a dedicated handler"
 *    exception, rather than persisting a bogus id.
 *
 * @group core
 * @group fields
 */
#[Group('core')]
#[Group('fields')]
class CustomModuleFieldKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'driver_field_test',
  ];

  /**
   * Tests a custom plain-scalar field with no handler rides the fallback.
   */
  public function testScalarFieldWithoutHandlerRoundTrips(): void {
    $this->attachField('field_scalar', 'driver_test_scalar');

    $this->assertFieldRoundTripViaDriver('field_scalar', [
      ['value' => 'a plain value', 'weight' => 5],
    ]);
  }

  /**
   * Tests a custom entity-reference field with no handler is refused.
   */
  public function testReferenceFieldWithoutHandlerIsRejected(): void {
    $this->attachField('field_ref', 'driver_test_reference');

    $stub = new EntityStub(self::ENTITY_TYPE, self::BUNDLE, [
      'name' => 'test entity',
      'field_ref' => [['target_id' => 1]],
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/No dedicated handler is registered.*entity-reference/s');

    $this->core->entityCreate($stub);
  }

}
