<?php

namespace Drupal\Tests\Driver;

use Drupal\Driver\DrushDriver;

class DrushDriverTest extends \PHPUnit_Framework_TestCase {

  function testWithAlias() {
    $driver = new DrushDriver('alias');
    $this->assertEquals('alias', $driver->alias, 'The drush alias was not properly set.');
  }

  function testWithAliasPrefix() {
    $driver = new DrushDriver('@alias');
    $this->assertEquals('alias', $driver->alias, 'The drush alias did not remove the "@" prefix.');
  }

  function testWithRoot() {
    // Bit of a hack here to use the path to this file, but all the driver cares
    // about during initialization is that the root be a directory.
    $driver = new DrushDriver('', __FILE__);
    $this->assertEquals(__FILE__, $driver->root);
  }

  /**
   * @expectedException \Drupal\Driver\Exception\BootstrapException
   */
  function testWithNeither() {
    new DrushDriver('', '');
  }

}
