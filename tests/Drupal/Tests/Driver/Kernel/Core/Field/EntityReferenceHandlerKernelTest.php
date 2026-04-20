<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel round-trip test for EntityReferenceHandler via the Core driver.
 *
 * The handler resolves human-readable labels (user names, node titles, etc.)
 * to entity ids. This test exercises the label-to-id lookup against a real
 * user, then verifies the stored target_id round-trips.
 *
 * @group fields
 */
#[Group('fields')]
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
   * Tests round-trip when a delta is an associative array.
   *
   * Callers passing the field-item shape used by file / image /
   * entity_reference_revisions values - e.g. '['target_id' => 'alice',
   * 'display' => 1]' - previously crashed because the array was handed to
   * 'query->condition()' directly, producing a SQL parameter-binding error.
   * The handler should treat the main property value as the lookup label,
   * resolve it to an id, and preserve the original array shape so any extra
   * item properties round-trip through to storage.
   */
  public function testUserReferenceResolvesAssociativeArrayDelta(): void {
    $this->attachField('field_owner', 'entity_reference', [
      'target_type' => 'user',
    ]);

    User::create(['name' => 'alice'])->save();

    $this->assertFieldRoundTripViaDriver('field_owner', [['target_id' => 'alice']]);
  }

  /**
   * Tests round-trip when deltas mix scalar labels and associative arrays.
   */
  public function testUserReferenceResolvesMixedScalarAndAssociativeDeltas(): void {
    // Needs an unlimited-cardinality field to store two deltas; attachField()
    // always creates a single-value field, so configure the storage inline.
    FieldStorageConfig::create([
      'field_name' => 'field_owners',
      'entity_type' => self::ENTITY_TYPE,
      'type' => 'entity_reference',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
      'settings' => ['target_type' => 'user'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_owners',
      'entity_type' => self::ENTITY_TYPE,
      'bundle' => self::BUNDLE,
    ])->save();

    User::create(['name' => 'alice'])->save();
    User::create(['name' => 'bob'])->save();

    $this->assertFieldRoundTripViaDriver('field_owners', [
      ['target_id' => 'alice'],
      'bob',
    ]);
  }

  /**
   * Tests round-trip for an entity_reference field targeting taxonomy terms.
   *
   * Drupal 8 beta10 removed the legacy 'taxonomy_term_reference' field type;
   * modern sites use 'entity_reference' with 'target_type = taxonomy_term',
   * so the driver routes through EntityReferenceHandler. Covered here
   * alongside the other EntityReferenceHandler targets rather than in its
   * own suite because it is the same handler exercising a different
   * 'target_type'.
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
