<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

use Drupal\user\Entity\User;

/**
 * Kernel round-trip test for EntityReferenceHandler via the Core driver.
 *
 * The handler resolves human-readable labels (user names, node titles, etc.)
 * to entity ids. This test exercises the label-to-id lookup against a real
 * user, then verifies the stored target_id round-trips.
 */
class EntityReferenceHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = self::BASE_MODULES;

  /**
   * Tests round-trip for an entity_reference field targeting users by name.
   */
  public function testUserReferenceByNameRoundTrip(): void {
    $this->attachField('field_owner', 'entity_reference', [
      'target_type' => 'user',
    ]);

    // Create a user that the handler can look up by name.
    $user = User::create(['name' => 'alice']);
    $user->save();

    // The handler resolves 'alice' to the user's uid; the base helper iterates
    // the driver-mutated stub so this works regardless of the id assigned.
    $this->assertFieldRoundTripViaDriver('field_owner', ['alice']);
  }

  /**
   * Tests round-trip when the value is already a numeric id.
   */
  public function testUserReferenceByIdRoundTrip(): void {
    $this->attachField('field_owner', 'entity_reference', [
      'target_type' => 'user',
    ]);

    $user = User::create(['name' => 'bob']);
    $user->save();

    $this->assertFieldRoundTripViaDriver('field_owner', [(int) $user->id()]);
  }

}
