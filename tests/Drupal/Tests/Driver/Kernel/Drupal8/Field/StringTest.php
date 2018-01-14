<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Field;

use Drupal\Tests\Driver\Kernel\Drupal8\Field\DriverFieldKernelTestBase;
use Drupal\entity_test\Entity\EntityTestBundle;

/**
 * Tests the driver's handling of string fields.
 *
 * @group driver
 */
class StringTest extends DriverFieldKernelTestBase
{

  /**
   * Machine name of the field type being tested.
   *
   * @string
   */
    protected $fieldType = 'string';

  /**
   * Test that an entity can be created with a single value in a string field.
   */
    public function testStringSingle()
    {
        $field = [$this->randomString()];
        $this->assertCreatedWithField($field);
    }

  /**
   * Test that an entity can be created with multiple values in a string field.
   */
    public function testStringMultiple()
    {
        $field = [$this->randomString(),$this->randomString()];
        $this->assertCreatedWithField($field);
    }

  /**
   * Test that an entity can be created with a single value in a string field.
   */
    public function testStringOnBundleField()
    {
        $this->installEntitySchema('entity_test_with_bundle');
        EntityTestBundle::create([
        'id' => 'test_bundle',
        'label' => 'Test label',
        'description' => 'Test description',
        ])->save();
        $field = [$this->randomString()];
        $entity = $this->createTestEntity($field, 'entity_test_with_bundle', 'test_bundle');
        $this->assertValidField($entity);
        $this->assertFieldValues($entity, $field);
    }
}
