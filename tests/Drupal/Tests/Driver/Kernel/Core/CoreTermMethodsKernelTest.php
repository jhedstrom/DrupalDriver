<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Driver\Core\Core;
use Drupal\Driver\Entity\EntityStub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for taxonomy term methods on Core via the driver.
 *
 * Exercises Core::termCreate (with optional parent lookup by name) and
 * Core::termDelete against real taxonomy_term storage.
 *
 * @group core
 */
#[Group('core')]
class CoreTermMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    'system',
    'user',
    'taxonomy',
    'text',
    'filter',
  ];

  /**
   * The Core driver under test.
   */
  protected Core $core;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['system', 'filter']);

    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();

    $this->core = new Core($this->root);
  }

  /**
   * Tests the term lifecycle: create with parent lookup, then delete.
   */
  public function testTermLifecycle(): void {
    $parent = Term::create(['name' => 'Frameworks', 'vid' => 'tags']);
    $parent->save();

    $child_stub = new EntityStub('taxonomy_term', 'tags', [
      'name' => 'Drupal',
      'parent' => 'Frameworks',
    ]);
    $result = $this->core->termCreate($child_stub);

    $this->assertSame($child_stub, $result);
    $this->assertNotEmpty($result->getValue('tid'));
    $this->assertTrue($result->isSaved());
    $child = Term::load($result->getValue('tid'));
    $this->assertInstanceOf(Term::class, $child);
    $this->assertSame('Drupal', $child->getName());
    $this->assertSame((int) $parent->id(), (int) $child->get('parent')->target_id, 'parent name was resolved to tid.');

    $this->assertTrue($this->core->termDelete($result));
    $this->assertNull(Term::load($result->getValue('tid')));
  }

  /**
   * Tests that termDelete returns FALSE for a non-existent term.
   */
  public function testTermDeleteReturnsFalseForMissingTerm(): void {
    $missing = new EntityStub('taxonomy_term', 'tags', ['tid' => 99999]);

    $this->assertFalse($this->core->termDelete($missing));
  }

  /**
   * Tests that termCreate rejects a stub missing the vocabulary.
   */
  public function testTermCreateRejectsMissingVocabularyProperty(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches("/missing the required property 'vocabulary_machine_name'/");

    $this->core->termCreate(new EntityStub('taxonomy_term', NULL, ['name' => 'Orphan']));
  }

  /**
   * Tests that termCreate rejects an unknown vocabulary.
   */
  public function testTermCreateRejectsUnknownVocabulary(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches("/vocabulary 'ghosts' does not exist/");

    $this->core->termCreate(new EntityStub('taxonomy_term', 'ghosts', [
      'name' => 'Casper',
    ]));
  }

  /**
   * Tests that termCreate rejects a parent term that does not exist.
   *
   * Previously a non-matching parent was silently left as the raw name string,
   * which produced an opaque downstream error from Term::create. Now it fails
   * loudly with a message that names the missing parent.
   */
  public function testTermCreateRejectsUnknownParent(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches("/parent term 'Missing' does not exist in vocabulary 'tags'/");

    $this->core->termCreate(new EntityStub('taxonomy_term', 'tags', [
      'name' => 'Orphaned',
      'parent' => 'Missing',
    ]));
  }

  /**
   * Tests that 'vocabulary_machine_name' on a stub selects the vocabulary.
   *
   * Existing happy-path coverage relies on the bundle constructor arg; this
   * test pins the alternative path that uses the alias as a stub value.
   */
  public function testTermCreateWithVocabularyMachineNameHint(): void {
    $stub = new EntityStub('taxonomy_term', NULL, [
      'name' => 'Drupal',
      'vocabulary_machine_name' => 'tags',
    ]);

    $result = $this->core->termCreate($stub);

    $this->assertTrue($result->isSaved(), 'termCreate marked the stub saved.');
    $this->assertFalse($result->hasValue('vocabulary_machine_name'), 'Alias removed after resolution.');
    $term = Term::load($result->getValue('tid'));
    $this->assertInstanceOf(Term::class, $term);
    $this->assertSame('tags', $term->bundle());
  }

}
