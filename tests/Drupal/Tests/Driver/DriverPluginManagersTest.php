<?php

namespace Drupal\Tests\Driver;

/**
 * Tests the Driver's plugin managers.
 */
class DriverPluginManagersTest extends \PHPUnit_Framework_TestCase {

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    \Mockery::close();
  }

  /**
   * Factory method to build and returned a mocked field handler.
   *
   * @param array $target
   *   The properties to find a matching plugin for.
   * @param array $mockFilters
   *   The possible filters for the mocked plugin manager.
   * @param array $mockCriteria
   *   The specificity criteria for the mocked plugin manager.
   * @param array $mockDefinitions
   *   The plugins to be discovered by the mocked plugin manager.
   *
   * @return array
   *   The ids of the matching mock definitions.
   */
  public function getMatchedPluginIds($target, $mockFilters, $mockCriteria, $mockDefinitions) {


    $mock = \Mockery::mock('Drupal\Driver\Plugin\DriverPluginManagerBase');
    $mock->makePartial();
    $mock->shouldAllowMockingProtectedMethods();
    $mock->shouldReceive('getFilters')->andReturn($mockFilters);
    $mock->shouldReceive('getSpecificityCriteria')->andReturn($mockCriteria);
    $mock->shouldReceive('getDefinitions')->andReturn($mockDefinitions);

    $matchedDefinitions = $mock->getMatchedDefinitions($target);
    $matchedIds = array_column($matchedDefinitions, 'id');
    return $matchedIds;
  }

  /**
   * Tests the plugin manager base's definition matching.
   *
   * @param array $target
   *   The properties to find a matching plugin for.
   * @param array $mockFilters
   *   The possible filters for the mocked plugin manager.
   * @param array $mockCriteria
   *   The specificity criteria for the mocked plugin manager.
   * @param array $mockDefinitions
   *   The plugins to be discovered by the mocked plugin manager.
   * @param array $expectedIds
   *   The ids of the mock definitions that match the target.
   *
   * @dataProvider managerBaseMatchedDefinitionsData
   */
  public function testManagerBaseMatchedDefinitions($target, $mockFilters, $mockCriteria, $mockDefinitions, $expectedIds) {
    $mock = \Mockery::mock('Drupal\Driver\Plugin\DriverPluginManagerBase');
    $mock->makePartial();
    $mock->shouldAllowMockingProtectedMethods();
    $mock->shouldReceive('getFilters')->andReturn($mockFilters);
    $mock->shouldReceive('getSpecificityCriteria')->andReturn($mockCriteria);
    $mock->shouldReceive('getDefinitions')->andReturn($mockDefinitions);

    $matchedDefinitions = $mock->getMatchedDefinitions($target);
    $ids = array_column($matchedDefinitions, 'id');
    $this->assertEquals($expectedIds, $ids);
  }

  /**
   * Data provider for testManagerBaseMatchedDefinitions().
   *
   * @return array
   *   An array of test data.
   */
  public function managerBaseMatchedDefinitionsData() {
    $mockFilters = ['a','b'];

    $mockCriteria = [
      ['a', 'b'],
      ['a'],
      ['b'],
    ];

    $mockDefinitions = [
      ['id' => 'A', 'weight' => 0, 'a' => [1], 'b' => [1],],
      ['id' => 'B', 'weight' => 0, 'a' => [1],],
      ['id' => 'C', 'weight' => 0, 'b' => [1],],
      ['id' => 'D', 'weight' => 0, 'a' => [2], 'b' => [1],],
      ['id' => 'E', 'weight' => 0, 'a' => [1], 'b' => [2],],
    ];

    $multivalueDefinitions = $mockDefinitions;
    $multivalueDefinitions[0]['a'] = [1,3];
    $multivalueDefinitions[2]['b'] = [1,2];

    $alphaAdjustedDefinitions = $mockDefinitions;
    $alphaAdjustedDefinitions[] = $alphaAdjustedDefinitions[0];
    $alphaAdjustedDefinitions[0]['id'] = 'F';


    return array(
      // Test non-matching values are rejected over multiple filters.
      [
        ['a' => 2, 'b' => 2],
        $mockFilters,
        $mockCriteria,
        $mockDefinitions,
        [],
      ],

      // Test all matching values are accepted.
      [
        ['a' => 1, 'b' => 1],
        $mockFilters,
        $mockCriteria,
        $mockDefinitions,
        ['A', 'B', 'C'],
      ],

      // Test specific comes before general regardless of definition order.
      [
        ['a' => 1, 'b' => 2],
        $mockFilters,
        $mockCriteria,
        $mockDefinitions,
        ['E', 'B'],
      ],

      // Test specific comes before general regardless of definition order.
      [
        ['a' => 2, 'b' => 1],
        $mockFilters,
        $mockCriteria,
        $mockDefinitions,
        ['D', 'C'],
      ],

      // Test weight overrules specificity.
      [
        ['a' => 1, 'b' => 1],
        $mockFilters,
        $mockCriteria,
        [
          ['id' => 'A', 'weight' => 0, 'a' => [1], 'b' => [1],],
          ['id' => 'B', 'weight' => 10, 'a' => [1],],
          ['id' => 'C', 'weight' => 0, 'b' => [1],],
          ['id' => 'D', 'weight' => 0, 'a' => [2], 'b' => [1],],
          ['id' => 'E', 'weight' => 0, 'a' => [1], 'b' => [2],],
        ],
        ['B', 'A', 'C'],
      ],

      // Test value in multivalue definitions.
      [
        ['a' => 1, 'b' => 1],
        $mockFilters,
        $mockCriteria,
        $multivalueDefinitions,
        ['A', 'B', 'C'],
      ],

      // Test plugins are sorted by id if weight and specificity are equal.
      [
        ['a' => 1, 'b' => 1],
        $mockFilters,
        $mockCriteria,
        $alphaAdjustedDefinitions,
        ['A', 'F', 'B', 'C'],
      ],

    );
  }

  /**
   * Tests the plugin manager base's definition matching.
   *
   * @param array $target
   *   The properties to find a matching plugin for.
   * @param array $mockFilters
   *   The possible filters for the mocked plugin manager.
   * @param array $mockCriteria
   *   The specificity criteria for the mocked plugin manager.
   * @param array $mockDefinitions
   *   The plugins to be discovered by the mocked plugin manager.
   * @param array $expectedIds
   *   The ids of the mock definitions that match the target.
   *
   * @dataProvider fieldManagerMatchedDefinitionsData
   */
  public function testFieldManagerMatchedDefinitions($target, $mockDefinitions, $expectedIds) {
    $mock = \Mockery::mock('Drupal\Driver\Plugin\DriverFieldPluginManager');
    $mock->makePartial();
    $mock->shouldReceive('getDefinitions')->andReturn($mockDefinitions);

    $matchedDefinitions = $mock->getMatchedDefinitions($target);
    $ids = array_column($matchedDefinitions, 'id');
    $this->assertEquals($expectedIds, $ids);
  }

  /**
   * Data provider for testManagerBaseMatchedDefinitions().
   *
   * @return array
   *   An array of test data.
   */
  public function fieldManagerMatchedDefinitionsData() {
    $mockDefinitions = [
      [
        'id' => 'A',
        'weight' => 0,
        'entityType' => ['node'],
        'fieldType' => ['datetime'],
        'fieldName' => ['datefield'],
      ],
      [
        'id' => 'B',
        'weight' => 0,
        'fieldType' => ['datetime'],
      ],
      [
        'id' => 'C',
        'weight' => 0,
        'entityType' => ['node'],
        'fieldName' => ['datefield'],
      ],
      [
        'id' => 'D',
        'weight' => 0,
        'entityType' => ['node'],
      ],
      [
        'id' => 'E',
        'weight' => 0,
        'entityType' => ['node'],
        'entityBundle' => ['article'],
        'fieldType' => ['datetime'],
        'fieldName' => ['datefield'],
      ],
      [
        'id' => 'F',
        'weight' => 0,
      ],
    ];

    $reweightedDefinitions = $mockDefinitions;
    $reweightedDefinitions[0]['weight'] = 10;

    return array(
      // Test specificity order.
      [
        [
          'entityType' => 'node',
          'entityBundle' => 'article',
          'fieldType' => 'datetime',
          'fieldName' => 'datefield'
        ],
        $mockDefinitions,
        ['E','A','C','B','D','F'],
      ],

      // Test entity type must not conflict.
      [
        [
          'entityType' => 'user',
          'entityBundle' => 'article',
          'fieldType' => 'datetime',
          'fieldName' => 'datefield'
        ],
        $mockDefinitions,
        ['B','F'],
      ],

      // Test entity bundle must not conflict.
      [
        [
          'entityType' => 'node',
          'entityBundle' => 'page',
          'fieldType' => 'datetime',
          'fieldName' => 'datefield'
        ],
        $mockDefinitions,
        ['A','C','B','D','F'],
      ],

      // Test field type must not conflict.
      [
        [
          'entityType' => 'node',
          'entityBundle' => 'article',
          'fieldType' => 'string',
          'fieldName' => 'datefield'
        ],
        $mockDefinitions,
        ['C','D','F'],
      ],

      // Test field name must not conflict.
      [
        [
          'entityType' => 'node',
          'entityBundle' => 'page',
          'fieldType' => 'datetime',
          'fieldName' => 'otherdatefield'
        ],
        $mockDefinitions,
        ['B','D','F'],
      ],

      // Weight trumps specificity.
      [
        [
          'entityType' => 'node',
          'entityBundle' => 'article',
          'fieldType' => 'datetime',
          'fieldName' => 'datefield'
        ],
        $reweightedDefinitions,
        ['A','E','C','B','D','F'],
      ],
    );
  }


}
