<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

use Drupal\entity_test\Entity\EntityTestNoLabel;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel tests for EntityReferenceHandler edge-case branches.
 *
 * Covers the target-bundles restriction path and the no-label-key path that
 * the main EntityReferenceHandlerKernelTest does not reach.
 *
 * @group fields
 */
#[Group('fields')]
class EntityReferenceHandlerEdgeCasesKernelTest extends FieldHandlerKernelTestBase {

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
    $this->installEntitySchema('entity_test_no_label');
  }

  /**
   * Tests the target_bundles restriction path resolves labels within bundle.
   */
  public function testTargetBundlesRestrictsMatches(): void {
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();
    Vocabulary::create(['vid' => 'categories', 'name' => 'Categories'])->save();

    // Matching term in the allowed bundle.
    Term::create(['name' => 'drupal', 'vid' => 'tags'])->save();
    // Same-named term in a disallowed bundle should be ignored by the query.
    Term::create(['name' => 'drupal', 'vid' => 'categories'])->save();

    $this->attachField(
      'field_tagged',
      'entity_reference',
      ['target_type' => 'taxonomy_term'],
      ['handler_settings' => ['target_bundles' => ['tags' => 'tags']]],
    );

    $this->assertFieldRoundTripViaDriver('field_tagged', ['drupal']);
  }

  /**
   * Tests the no-label-key branch resolves the reference by id.
   *
   * 'entity_test_no_label' exposes no label entity key, so the handler falls
   * through to the id-only condition branch.
   */
  public function testNoLabelKeyBranchUsesIdCondition(): void {
    $target = EntityTestNoLabel::create(['name' => 'nolabel-target']);
    $target->save();

    $this->attachField('field_ref', 'entity_reference', [
      'target_type' => 'entity_test_no_label',
    ]);

    $this->assertFieldRoundTripViaDriver('field_ref', [(int) $target->id()]);
  }

}
