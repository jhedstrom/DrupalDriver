<?php

declare(strict_types=1);

namespace Drupal\driver_field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Test field type whose columns are all plain scalars.
 *
 * The driver ships no handler for this type, so it exercises the DefaultHandler
 * fallback: 'Core' relays its records to storage verbatim.
 */
#[FieldType(
  id: 'driver_test_scalar',
  label: new TranslatableMarkup('Driver test scalar'),
)]
class DriverTestScalarItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Value'))
      ->setRequired(TRUE);

    $properties['weight'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Weight'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'weight' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $value = $this->get('value')->getValue();

    return $value === NULL || $value === '';
  }

}
