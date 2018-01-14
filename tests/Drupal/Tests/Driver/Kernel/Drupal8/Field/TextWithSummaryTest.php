<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;

/**
 * Tests the driver's handling of text_with_summary fields.
 *
 * @group driver
 */
class TextWithSummaryTest extends DriverFieldKernelTestBase
{

  /**
   * Machine name of the field type being tested.
   *
   * @string
   */
    protected $fieldType = 'text_with_summary';

  /**
   * Test single value with summary and main text.
   */
    public function testSummarySingle()
    {
        $field = [[
        'value' => $this->randomString(),
        'summary' => $this->randomString(),
        ]];
        $this->assertCreatedWithField($field);
    }

  /**
   * Test multiple value with summary and main text.
   */
    public function testSummaryMultiple()
    {
        $field = [
        [
        'value' => $this->randomString(),
        'summary' => $this->randomString(),
        ],
        [
        'value' => $this->randomString(),
        'summary' => $this->randomString(),
        ],
        ];
        $this->assertCreatedWithField($field);
    }

  /**
   * Test single value with no summary.
   */
    public function testNoSummarySingle()
    {
        $field = [[
        'value' => $this->randomString(),
        ]];
        $this->assertCreatedWithField($field);
    }

  /**
   * Test multiple value with and without summary.
   */
    public function testMixedMultiple()
    {
        $field = [
        [
        'value' => $this->randomString(),
        ],
        [
        'value' => $this->randomString(),
        'summary' => $this->randomString(),
        ],
        ];
        $this->assertCreatedWithField($field);
    }
}
