<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

/**
 * Tests the driver's handling of link fields.
 *
 * @group driver
 */
class LinkTest extends DriverFieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'field', 'link'];

  /**
   * Machine name of the field type being tested.
   *
   * @var string
   */
  protected $fieldType = 'link';

  /**
   * Test link field with named properties.
   */
  public function testLinkWithPropertyNames() {
    $fieldExpected = [[
      'uri' => 'http://' . $this->randomMachineName() . '.com',
      'title' => $this->randomMachineName(),
      'options' => ['query' => 'hgf', 'fragment' => 'jju'],
    ],
    ];
    $field = [[
      'uri' => $fieldExpected[0]['uri'],
      'title' => $fieldExpected[0]['title'],
      'options' => 'query=hgf&fragment=jju',
    ],
    ];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test link field without options.
   */
  public function testLinkWithoutOptions() {
    $fieldExpected = [[
      'title' => $this->randomMachineName(),
      'uri' => 'http://' . $this->randomMachineName() . '.com',
    ],
    ];
    $field = [[
      $fieldExpected[0]['title'],
      $fieldExpected[0]['uri'],
    ],
    ];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test link field with options.
   */
  public function testLinkWithOptions() {
    $fieldExpected = [[
      'title' => $this->randomMachineName(),
      'uri' => 'http://' . $this->randomMachineName() . '.com',
      'options' => ['query' => 'hgf', 'fragment' => 'jju'],
    ],
    ];
    $field = [[
      $fieldExpected[0]['title'],
      $fieldExpected[0]['uri'],
      'query=hgf&fragment=jju',
    ],
    ];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test link field with multiple values.
   */
  public function testLinkMultiple() {
    $fieldExpected = [
        [
          'title' => $this->randomMachineName(),
          'uri' => 'http://' . $this->randomMachineName() . '.com',
        ],
        [
          'title' => $this->randomMachineName(),
          'uri' => 'http://' . $this->randomMachineName() . '.com',
        ],
    ];
    $field = [
        [
          $fieldExpected[0]['title'],
          $fieldExpected[0]['uri'],
        ],
        [
          $fieldExpected[1]['title'],
          $fieldExpected[1]['uri'],
        ],
    ];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test link field title default.
   */
  public function testLinkTitleDefaultNoUriKey() {
    $uri = 'http://' . $this->randomMachineName() . '.com';
    $fieldExpected = [[
      'uri' => $uri,
      'title' => $uri,
      'options' => [],
    ],
    ];
    $field = [[
      $fieldExpected[0]['uri'],
    ],
    ];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

  /**
   * Test link field title default.
   */
  public function testLinkTitleDefaultWithUriKey() {
    $uri = 'http://' . $this->randomMachineName() . '.com';
    $fieldExpected = [[
      'uri' => $uri,
      'title' => $uri,
      'options' => [],
    ],
    ];
    $field = [[
      'uri' => $fieldExpected[0]['uri'],
    ],
    ];
    $this->assertCreatedWithField($field, $fieldExpected);
  }

}
