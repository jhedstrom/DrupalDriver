<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Driver\Core\Core;
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

    $child_stub = (object) [
      'vocabulary_machine_name' => 'tags',
      'name' => 'Drupal',
      'parent' => 'Frameworks',
    ];
    $result = $this->core->termCreate($child_stub);

    $this->assertNotEmpty($result->tid);
    $child = Term::load($result->tid);
    $this->assertInstanceOf(Term::class, $child);
    $this->assertSame('Drupal', $child->getName());
    $this->assertSame((int) $parent->id(), (int) $child->get('parent')->target_id, 'parent name was resolved to tid.');

    $this->assertTrue($this->core->termDelete($result));
    $this->assertNull(Term::load($result->tid));
  }

  /**
   * Tests that termDelete returns FALSE for a non-existent term.
   */
  public function testTermDeleteReturnsFalseForMissingTerm(): void {
    $missing = (object) ['tid' => 99999];

    $this->assertFalse($this->core->termDelete($missing));
  }

  /**
   * Tests that termCreate rejects a stub missing 'vocabulary_machine_name'.
   */
  public function testTermCreateRejectsMissingVocabularyProperty(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches("/missing the required property 'vocabulary_machine_name'/");

    $this->core->termCreate((object) ['name' => 'Orphan']);
  }

  /**
   * Tests that termCreate rejects an unknown vocabulary.
   */
  public function testTermCreateRejectsUnknownVocabulary(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches("/vocabulary 'ghosts' does not exist/");

    $this->core->termCreate((object) [
      'vocabulary_machine_name' => 'ghosts',
      'name' => 'Casper',
    ]);
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

    $this->core->termCreate((object) [
      'vocabulary_machine_name' => 'tags',
      'name' => 'Orphaned',
      'parent' => 'Missing',
    ]);
  }

}
