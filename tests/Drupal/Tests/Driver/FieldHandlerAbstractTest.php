<?php

/**
 * @file
 * Contains \Drupal\Tests\Driver\FieldHandlerAbstractTest
 */

namespace Drupal\Tests\Driver;

use \Mockery as m;

/**
 * Class FieldHandlerAbstractTest
 * @package Drupal\Tests\Driver
 */
abstract class FieldHandlerAbstractTest extends \PHPUnit_Framework_TestCase {

  public function tearDown()
  {
    m::close();
  }

  /**
   * factory method to build and returned a mocked field handler.
   *
   * @param $handler
   * @param $entity
   * @param $entity_type
   * @param $field
   * @return \Mockery\MockInterface
   */
  protected function getMockHandler($handler, $entity, $entity_type, $field)
  {
    $mock = m::mock(sprintf('Drupal\Driver\Fields\Drupal7\%s', $handler));
    $mock->makePartial();
    $mock->shouldReceive('getFieldInfo')->andReturn($field);
    $mock->shouldReceive('getEntityLanguage')->andReturn('en');
    $mock->__construct($entity, $entity_type, $field);
    return $mock;
  }

  /**
   * Simulate __call() since mocked handlers will not run through magic methods.
   *
   * @param $values
   * @return array
   */
  protected function values($values)
  {
    return (array) $values;
  }

}
