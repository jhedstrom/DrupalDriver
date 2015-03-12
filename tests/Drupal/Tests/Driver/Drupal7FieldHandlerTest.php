<?php

/**
 * @file
 * Contains \Drupal\Tests\Driver\Drupal7FieldHandlerTest
 */

namespace Drupal\Tests\Driver;

/**
 * Class Drupal7FieldHandlerTest
 * @package Drupal\Tests\Driver
 */
class Drupal7FieldHandlerTest extends FieldHandlerAbstractTest {

  /**
   * @dataProvider dataProvider
   */
  public function testFieldHandlers($class_name, $entity, $entity_type, $field, $expected_values)
  {
    $handler = $this->getMockHandler($class_name, $entity, $entity_type, $field);

    $field_name = $field['field_name'];
    $expanded_values = $handler->expand($this->values($entity->$field_name));
    $this->assertArraySubset($expected_values, $expanded_values);
  }

  /**
   * Data provider.
   *
   * @return array
   */
  public function dataProvider()
  {
    return [

      // Test default text field provided as simple text.
      [
        'DefaultHandler',
        (object) ['field_text' => 'Text'],
        'node',
        ['field_name' => 'field_text'],
        ['en' => [['value' => 'Text']]],
      ],

      // Test default text field provided as array.
      [
        'DefaultHandler',
        (object) ['field_text' => ['Text']],
        'node',
        ['field_name' => 'field_text'],
        ['en' => [['value' => 'Text']]],
      ],

      // Test single-value date field provided as simple text.
      [
        'DatetimeHandler',
        (object) ['field_date' => '2015-01-01 00:00:00'],
        'node',
        ['field_name' => 'field_date'],
        ['en' => [['value' => '2015-01-01 00:00:00']]],
      ],

      // Test single-value date field provided as an array.
      [
        'DatetimeHandler',
        (object) ['field_date' => ['2015-01-01 00:00:00']],
        'node',
        ['field_name' => 'field_date'],
        ['en' => [['value' => '2015-01-01 00:00:00']]],
      ],

      // Test double-value date field. Can only be provided as an array
      // due to array type casting we perform in
      // \Drupal\Driver\Fields\Drupal7\AbstractFieldHandler::__call()
      [
        'DatetimeHandler',
        (object) ['field_date' => [['2015-01-01 00:00:00', '2015-01-02 00:00:00']]],
        'node',
        ['field_name' => 'field_date', 'columns' => ['value' => '', 'value2' => '']],
        ['en' => [['value' => '2015-01-01 00:00:00', 'value2' => '2015-01-02 00:00:00']]],
      ],

    ];
  }

}
