<?php

declare(strict_types=1);

namespace Drupal\driver_field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Test field type with an entity-reference target column.
 *
 * The driver ships no handler for this type. Its stored 'target_id' is an id a
 * label cannot supply, so the field shape classifier flags it as an entity
 * reference and 'Core' refuses it at handler resolution rather than relaying a
 * bogus value.
 */
#[FieldType(
  id: 'driver_test_reference',
  label: new TranslatableMarkup('Driver test reference'),
)]
class DriverTestReferenceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName(): string {
    return 'target_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['target_id'] = DataReferenceTargetDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Referenced entity ID'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'target_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $value = $this->get('target_id')->getValue();

    return $value === NULL || $value === '';
  }

}
