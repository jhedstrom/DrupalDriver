<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;

/**
 * Tests the driver's handling of link fields.
 *
 * @group driver
 */
class LinkTest extends DriverFieldKernelTestBase {

  // @todo add a test for handling of named keys in field input.

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'field', 'link'];

  /**
   * Machine name of the field type being tested.
   *
   * @string
   */
  protected $fieldType = 'link';

  /**
   * Test link field without options.
   */
  public function testLinkWithoutOptions() {
    $fieldExpected = [[
      'title' => $this->randomMachineName(),
      'uri' => 'http://' . $this->randomMachineName() . '.com',
    ]];
    $field = [[
      $fieldExpected[0]['title'],
      $fieldExpected[0]['uri'],
    ]];
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
      ]];
    $field = [[
      $fieldExpected[0]['title'],
      $fieldExpected[0]['uri'],
      'query=hgf&fragment=jju',
    ]];
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

}
