<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Hint;

use Drupal\Driver\Core\Hint\VocabularyMachineNameHint;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Hint\PreCreateHintInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the 'VocabularyMachineNameHint' creation hint.
 *
 * @group hints
 */
#[Group('hints')]
class VocabularyMachineNameHintTest extends TestCase {

  /**
   * Tests metadata accessors.
   */
  public function testMetadataAccessors(): void {
    $hint = new VocabularyMachineNameHint();

    $this->assertInstanceOf(PreCreateHintInterface::class, $hint);
    $this->assertSame('vocabulary_machine_name', $hint->getName());
    $this->assertSame('taxonomy_term', $hint->getEntityType());
    $this->assertNotSame('', $hint->getDescription());
  }

  /**
   * Tests resolution behaviour across stub shapes.
   *
   * @param string|null $bundle
   *   The bundle passed to the stub constructor.
   * @param array<string, mixed> $values
   *   The initial stub values.
   * @param string|null $expected_vid
   *   The expected 'vid' value after the hint runs, or NULL when 'vid'
   *   should remain absent.
   */
  #[DataProvider('dataProviderApplyToStub')]
  public function testApplyToStub(?string $bundle, array $values, ?string $expected_vid): void {
    $hint = new VocabularyMachineNameHint();
    $stub = new EntityStub('taxonomy_term', $bundle, $values);

    $hint->applyToStub($stub);

    $this->assertFalse($stub->hasValue('vocabulary_machine_name'), 'Alias must be removed after the hint runs.');

    if ($expected_vid === NULL) {
      $this->assertFalse($stub->hasValue('vid'));
    }
    else {
      $this->assertSame($expected_vid, $stub->getValue('vid'));
    }
  }

  /**
   * Data provider for 'testApplyToStub()'.
   *
   * @return iterable<string, array<int, mixed>>
   *   Cases of bundle, stub values, expected 'vid' (or NULL).
   */
  public static function dataProviderApplyToStub(): iterable {
    yield 'no bundle, alias only' => [
      NULL,
      ['vocabulary_machine_name' => 'tags'],
      'tags',
    ];
    yield 'bundle wins over alias' => [
      'categories',
      ['vocabulary_machine_name' => 'tags'],
      NULL,
    ];
    yield 'explicit vid wins over alias' => [
      NULL,
      ['vocabulary_machine_name' => 'tags', 'vid' => 'categories'],
      'categories',
    ];
    yield 'empty alias copies through' => [
      NULL,
      ['vocabulary_machine_name' => ''],
      '',
    ];
  }

}
