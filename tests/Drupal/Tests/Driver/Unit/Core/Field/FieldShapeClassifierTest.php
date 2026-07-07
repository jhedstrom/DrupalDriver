<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Driver\Core\Field\FieldShapeClassifier;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests value-shape classification by stored property definitions.
 *
 * @group core
 * @group fields
 */
#[Group('core')]
#[Group('fields')]
class FieldShapeClassifierTest extends TestCase {

  /**
   * Tests entity-reference detection by a DataReferenceTargetDefinition.
   */
  public function testFieldIsEntityReference(): void {
    $classifier = new FieldShapeClassifier();

    $this->assertTrue($classifier->fieldIsEntityReference($this->storageWithProperties([
      'target_id' => DataReferenceTargetDefinition::create('integer'),
    ])));

    // A plain scalar is not a reference.
    $this->assertFalse($classifier->fieldIsEntityReference($this->storageWithProperties([
      'value' => DataDefinition::create('string'),
    ])));

    // A datetime column is a scalar, not a reference.
    $this->assertFalse($classifier->fieldIsEntityReference($this->storageWithProperties([
      'value' => DataDefinition::create('datetime_iso8601'),
    ])));

    // A computed reference is storage-derived, not author-supplied, so it is
    // ignored.
    $this->assertFalse($classifier->fieldIsEntityReference($this->storageWithProperties([
      'value' => DataDefinition::create('string'),
      'entity' => DataReferenceTargetDefinition::create('integer')->setComputed(TRUE),
    ])));
  }

  /**
   * Tests complex-value detection by a ComplexDataDefinitionInterface.
   */
  public function testFieldIsComplexValue(): void {
    $classifier = new FieldShapeClassifier();

    $this->assertTrue($classifier->fieldIsComplexValue($this->storageWithProperties([
      'value' => DataDefinition::create('string'),
      'options' => MapDataDefinition::create(),
    ])));

    // Plain scalars are not complex.
    $this->assertFalse($classifier->fieldIsComplexValue($this->storageWithProperties([
      'value' => DataDefinition::create('string'),
      'format' => DataDefinition::create('string'),
    ])));
  }

  /**
   * Builds a storage definition mock exposing the given property definitions.
   *
   * @param array<string, \Drupal\Core\TypedData\DataDefinitionInterface> $properties
   *   Property definitions keyed by property name.
   */
  protected function storageWithProperties(array $properties): FieldStorageDefinitionInterface {
    $storage = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage->method('getPropertyDefinitions')->willReturn($properties);

    return $storage;
  }

}
