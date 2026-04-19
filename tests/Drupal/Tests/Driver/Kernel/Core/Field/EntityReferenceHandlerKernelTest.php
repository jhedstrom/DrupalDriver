<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
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
  protected static $modules = [
    ...self::BASE_MODULES,
    'taxonomy',
    'text',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
  }

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

  /**
   * Tests round-trip for an entity_reference field targeting taxonomy terms.
   *
   * Modern Drupal uses entity_reference for taxonomy links, so the driver
   * resolves EntityReferenceHandler rather than the legacy
   * TaxonomyTermReferenceHandler. Kept here rather than a separate test
   * because it's the same handler exercising a different target_type.
   */
  public function testTaxonomyTermReferenceByNameRoundTrip(): void {
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();
    Term::create(['name' => 'drupal', 'vid' => 'tags'])->save();

    $this->attachField('field_tags', 'entity_reference', [
      'target_type' => 'taxonomy_term',
    ]);

    $this->assertFieldRoundTripViaDriver('field_tags', ['drupal']);
  }

}
