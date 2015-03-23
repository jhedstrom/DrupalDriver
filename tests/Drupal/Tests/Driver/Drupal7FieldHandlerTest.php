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
    return array(

      // Test default text field provided as simple text.
      array(
        'DefaultHandler',
        (object) array('field_text' => 'Text'),
        'node',
        array('field_name' => 'field_text'),
        array('en' => array(array('value' => 'Text'))),
      ),

      // Test default text field provided as array.
      array(
        'DefaultHandler',
        (object) array('field_text' => array('Text')),
        'node',
        array('field_name' => 'field_text'),
        array('en' => array(array('value' => 'Text'))),
      ),

      // Test single-value date field provided as simple text.
      array(
        'DatetimeHandler',
        (object) array('field_date' => '2015-01-01 00:00:00'),
        'node',
        array('field_name' => 'field_date'),
        array('en' => array(array('value' => '2015-01-01 00:00:00'))),
      ),

      // Test single-value date field provided as an array.
      array(
        'DatetimeHandler',
        (object) array('field_date' => array('2015-01-01 00:00:00')),
        'node',
        array('field_name' => 'field_date'),
        array('en' => array(array('value' => '2015-01-01 00:00:00'))),
      ),

      // Test double-value date field. Can only be provided as an array
      // due to array type casting we perform in
      // \Drupal\Driver\Fields\Drupal7\AbstractFieldHandler::__call()
      array(
        'DatetimeHandler',
        (object) array('field_date' => array(array('2015-01-01 00:00:00', '2015-01-02 00:00:00'))),
        'node',
        array('field_name' => 'field_date', 'columns' => array('value' => '', 'value2' => '')),
        array('en' => array(array('value' => '2015-01-01 00:00:00', 'value2' => '2015-01-02 00:00:00'))),
      ),

    );
  }

}
