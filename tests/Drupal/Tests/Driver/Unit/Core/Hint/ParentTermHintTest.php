<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Hint;

use Drupal\Driver\Core\Hint\ParentTermHint;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Exception\CreationHintResolutionException;
use Drupal\Driver\Hint\PreCreateHintInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the 'ParentTermHint' creation hint.
 *
 * @group hints
 */
#[Group('hints')]
class ParentTermHintTest extends TestCase {

  /**
   * Tests metadata accessors.
   */
  public function testMetadataAccessors(): void {
    $hint = new ParentTermHint(static fn (): ?int => NULL);

    $this->assertInstanceOf(PreCreateHintInterface::class, $hint);
    $this->assertSame('parent', $hint->getName());
    $this->assertSame('taxonomy_term', $hint->getEntityType());
    $this->assertNotSame('', $hint->getDescription());
  }

  /**
   * Tests that a resolved parent term replaces the value in place.
   */
  public function testApplyToStubResolvesParent(): void {
    $hint = new ParentTermHint(static fn (string $name, string $vid): int => $name === 'Frameworks' && $vid === 'tags' ? 99 : 0);

    $stub = new EntityStub('taxonomy_term', 'tags', ['name' => 'Symfony', 'parent' => 'Frameworks']);

    $hint->applyToStub($stub);

    $this->assertSame(99, $stub->getValue('parent'));
  }

  /**
   * Tests that 'vid' from the stub is used when no bundle is present.
   */
  public function testApplyToStubFallsBackToVidValue(): void {
    $received_vid = NULL;
    $hint = new ParentTermHint(static function (string $name, string $vid) use (&$received_vid): int {
      $received_vid = $vid;

      return 7;
    });

    $stub = new EntityStub('taxonomy_term', NULL, ['parent' => 'Frameworks', 'vid' => 'tags']);

    $hint->applyToStub($stub);

    $this->assertSame('tags', $received_vid);
    $this->assertSame(7, $stub->getValue('parent'));
  }

  /**
   * Tests that unresolved parents throw and the value is left alone.
   */
  public function testApplyToStubThrowsOnUnknownParent(): void {
    $hint = new ParentTermHint(static fn (): ?int => NULL);

    $stub = new EntityStub('taxonomy_term', 'tags', ['parent' => 'Nope']);

    try {
      $hint->applyToStub($stub);
      $this->fail('Expected CreationHintResolutionException.');
    }
    catch (CreationHintResolutionException $e) {
      $this->assertStringContainsString("'Nope'", $e->getMessage());
      $this->assertStringContainsString("'tags'", $e->getMessage());
      $this->assertSame('Nope', $stub->getValue('parent'));
    }
  }

  /**
   * Tests that empty parent values short-circuit without calling lookup.
   *
   * @param mixed $parent
   *   The empty-ish value placed on the stub.
   */
  #[DataProvider('dataProviderApplyToStubNoOpsOnEmptyParent')]
  public function testApplyToStubNoOpsOnEmptyParent(mixed $parent): void {
    $calls = 0;
    $hint = new ParentTermHint(static function () use (&$calls): int {
      $calls++;

      return 1;
    });

    $stub = new EntityStub('taxonomy_term', 'tags', ['parent' => $parent]);

    $hint->applyToStub($stub);

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
