<?php

namespace Drupal\Tests\Driver;

use Drupal\Driver\Plugin\DriverNameMatcher;

/**
 * Tests the Driver's name matcher utility.
 */
class DriverNameMatcherTest extends \PHPUnit_Framework_TestCase
{

  /**
   * Tests the driver name matcher's identify() method..
   *
   * @param string $identifier
   *   The string to identify the right candidate by.
   * @param string $expected
   *   The machine name of the correct candidate.
   * @param array $candidates
   *   The candidate items to identify from amongst.
   *
   * @dataProvider identifyData
   */
    public function testIdentify($identifier, $expected, $candidates)
    {
        $prefix = "field_";
        $matcher = new DriverNameMatcher($candidates, $prefix);
        $actual = $matcher->identify($identifier);
        $this->assertEquals($expected, $actual);
    }

  /**
   * Data provider for testIdentify().
   *
   * @return array
   *   An array of test data.
   */
    public function identifyData()
    {

        $candidates = [
        'Start date' => 'startdate',
        'end date' => 'enddate',
        'Summary' => 'description',
        'Long summary' => 'description',
        'casual' => 'field_dress',
        'Place' => 'field_location',
        'location' =>'location_address',
        'Speaker name' => 'field_speaker_name'
        ];

        return array(
        // Test identifying by machine name exact match.
        [
        'startdate',
        'startdate',
        $candidates,
        ],

        // Test identifying by machine name exact match is case insensitive.
        [
        'STARTdate',
        'startdate',
        $candidates,
        ],

        // Test identifying by label exact match (case insensitively).
        [
        'summary',
        'description',
        $candidates,
        ],

        // Test identifying by multiple labels.
        [
        'Long summary',
        'description',
        $candidates,
        ],

        // Test identifying by machine name without prefix.
        [
        'dress',
        'field_dress',
        $candidates,
        ],

        // Test identifying label exact match trumps machine name without prefix.
        [
        'location',
        'location_address',
        $candidates,
        ],

        // Test identifying by machine name without underscores.
        [
        'location address',
        'location_address',
        $candidates,
        ],

        // Test identifying by machine name without underscores or prefix.
        [
        'Speaker name',
        'field_speaker_name',
        $candidates,
        ],

        );
    }


  /**
   * Tests the driver name matcher's identifySet() method..
   */
    public function testIdentifySet()
    {
        $candidates = [
        'Start date' => 'startdate',
        'end date' => 'enddate',
        'Summary' => 'description',
        'Long summary' => 'description',
        'casual' => 'field_dress',
        'Place' => 'field_address',
        'address' => 'location',
        'Speaker name' => 'field_speaker_name',
        'Full summary' => 'long_summary'
        ];

        $targets = [
        'startdate' => 10,
        'address' => 30,
        'speaker name' => 40,
        'location' => 20,
        'summary' => 50,
        'long summary' =>60
        ];

        $prefix = "field_";
        $matcher = new DriverNameMatcher($candidates, $prefix);
        $results = $matcher->identifySet($targets);

        $this->assertEquals(6, count($results));
        $this->assertEquals(10, $results['startdate']);
        // Location machine name exact match (even though last in sequence)
        // makes location field unavailable for a label match with 'address'
        $this->assertEquals(20, $results['location']);
        $this->assertEquals(30, $results['field_address']);
        $this->assertEquals(40, $results['field_speaker_name']);
        $this->assertEquals(50, $results['description']);
        // Once a field is matched, all labels for it are no longer available.
        $this->assertEquals(60, $results['long_summary']);
    }
}
