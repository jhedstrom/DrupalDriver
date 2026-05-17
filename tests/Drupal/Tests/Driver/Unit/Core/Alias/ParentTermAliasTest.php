<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Alias;

use Drupal\Driver\Alias\PreCreateAliasInterface;
use Drupal\Driver\Core\Alias\ParentTermAlias;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Exception\CreationAliasResolutionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the 'ParentTermAlias' creation alias.
 *
 * @group aliases
 */
#[Group('aliases')]
class ParentTermAliasTest extends TestCase {

  /**
   * Tests metadata accessors.
   */
  public function testMetadataAccessors(): void {
    $alias = new ParentTermAlias(static fn (): ?int => NULL);

    $this->assertInstanceOf(PreCreateAliasInterface::class, $alias);
    $this->assertSame('parent', $alias->getName());
    $this->assertSame('taxonomy_term', $alias->getEntityType());
    $this->assertNotSame('', $alias->getDescription());
  }

  /**
   * Tests that a resolved parent term replaces the value in place.
   */
  public function testApplyToStubResolvesParent(): void {
    $alias = new ParentTermAlias(static fn (string $name, string $vid): int => $name === 'Frameworks' && $vid === 'tags' ? 99 : 0);

    $stub = new EntityStub('taxonomy_term', 'tags', ['name' => 'Symfony', 'parent' => 'Frameworks']);

    $alias->applyToStub($stub);

    $this->assertSame(99, $stub->getValue('parent'));
  }

  /**
   * Tests that 'vid' from the stub is used when no bundle is present.
   */
  public function testApplyToStubFallsBackToVidValue(): void {
    $received_vid = NULL;
    $alias = new ParentTermAlias(static function (string $name, string $vid) use (&$received_vid): int {
      $received_vid = $vid;

      return 7;
    });

    $stub = new EntityStub('taxonomy_term', NULL, ['parent' => 'Frameworks', 'vid' => 'tags']);

    $alias->applyToStub($stub);

    $this->assertSame('tags', $received_vid);
    $this->assertSame(7, $stub->getValue('parent'));
  }

  /**
   * Tests that unresolved parents throw and the value is left alone.
   */
  public function testApplyToStubThrowsOnUnknownParent(): void {
    $alias = new ParentTermAlias(static fn (): ?int => NULL);

    $stub = new EntityStub('taxonomy_term', 'tags', ['parent' => 'Nope']);

    try {
      $alias->applyToStub($stub);
      $this->fail('Expected CreationAliasResolutionException.');
    }
    catch (CreationAliasResolutionException $e) {
      $this->assertStringContainsString("'Nope'", $e->getMessage());
      $this->assertStringContainsString("'tags'", $e->getMessage());
      $this->assertSame('Nope', $stub->getValue('parent'));
    }
  }

  /**
   * Tests that a missing vocabulary throws with a clear message.
   *
   * Pins the new strict-failure branch added when 'parent' is resolved
   * without a bundle or 'vid' to scope the term lookup. Without this
   * coverage, the missing-vocabulary path would only be exercised
   * indirectly via the parent-not-found assertion.
   */
  public function testApplyToStubThrowsOnMissingVocabulary(): void {
    $alias = new ParentTermAlias(static fn (): int => 1);

    $stub = new EntityStub('taxonomy_term', NULL, ['parent' => 'Frameworks']);

    try {
      $alias->applyToStub($stub);
      $this->fail('Expected CreationAliasResolutionException.');
    }
    catch (CreationAliasResolutionException $e) {
      $this->assertStringContainsString("'Frameworks'", $e->getMessage());
      $this->assertStringContainsString('no vocabulary', $e->getMessage());
      $this->assertSame('Frameworks', $stub->getValue('parent'));
    }
  }

  /**
   * Tests that empty parent values short-circuit without calling lookup.
   *
   * @param mixed $parent
   *   The empty-ish value placed on the stub.
   *
   * @dataProvider dataProviderApplyToStubNoOpsOnEmptyParent
   */
  #[DataProvider('dataProviderApplyToStubNoOpsOnEmptyParent')]
  public function testApplyToStubNoOpsOnEmptyParent(mixed $parent): void {
    $calls = 0;
    $alias = new ParentTermAlias(static function () use (&$calls): int {
      $calls++;

      return 1;
    });

    $stub = new EntityStub('taxonomy_term', 'tags', ['parent' => $parent]);

    $alias->applyToStub($stub);

    $this->assertSame(0, $calls, 'Lookup must not be invoked for empty values.');
    $this->assertSame($parent, $stub->getValue('parent'), 'Value must remain unchanged.');
  }

  /**
   * Data provider for 'testApplyToStubNoOpsOnEmptyParent()'.
   *
   * @return iterable<string, array<int, mixed>>
   *   Cases of empty-ish parent value.
   */
  public static function dataProviderApplyToStubNoOpsOnEmptyParent(): iterable {
    yield 'empty string' => [''];
    yield 'null' => [NULL];
    yield 'zero integer' => [0];
    yield 'empty array' => [[]];
  }

}
