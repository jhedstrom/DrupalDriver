<?php

namespace Drupal\Tests\Driver\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Driver\Fields\Drupal8\LinkHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests LinkHandler.
 *
 * @coversDefaultClass \Drupal\Driver\Fields\Drupal8\LinkHandler
 */
class LinkHandlerTest extends TestCase {

  const TEST_ENTITY_TYPE_ID = 'test_entity';
  const TEST_FIELD_ID = 'test_field';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityType = $this->getMockBuilder(EntityTypeInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityType->method('getKey')
      ->with('bundle')
      ->willReturn('bundle');

    $entityTypeManager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityTypeManager->method('getDefinition')
      ->with(self::TEST_ENTITY_TYPE_ID)
      ->willReturn($entityType);

    $fieldDefinition = $this->getMockBuilder(FieldDefinitionInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityFieldManager = $this->getMockBuilder(EntityFieldManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityFieldManager->method('getFieldStorageDefinitions')
      ->with(self::TEST_ENTITY_TYPE_ID)
      ->willReturn([
        self::TEST_FIELD_ID => [],
      ]);
    $entityFieldManager->method('getFieldDefinitions')
      ->with(self::TEST_ENTITY_TYPE_ID, self::TEST_ENTITY_TYPE_ID)
      ->willReturn([
        self::TEST_FIELD_ID => $fieldDefinition,
      ]);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityTypeManager);
    $container->set('entity_field.manager', $entityFieldManager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::expand
   *
   * @dataProvider providerTestLinkHandler
   */
  public function testLinkHandler(array $values, array $expected): void {
    $entityObject = [
      'bundle' => self::TEST_ENTITY_TYPE_ID,
      self::TEST_FIELD_ID => $values,
    ];
    $linkHandler = new LinkHandler(
      (object) $entityObject,
      self::TEST_ENTITY_TYPE_ID,
      self::TEST_FIELD_ID,
    );

    $this->assertSame($expected, $linkHandler->expand($entityObject[self::TEST_FIELD_ID]));
  }

  /**
   * Data provider for ::testLinkHandler.
   *
   * @return array[][]
   *   The test cases.
   */
  public function providerTestLinkHandler(): array {
    return [
      'Link with only URI' => [
        'values' => [
          'https://example.com',
        ],
        'expected' => [
          [
            'options' => [],
            'uri' => 'https://example.com',
          ],
        ],
      ],
      'Link with title and URI' => [
        'values' => [
          ['Link title', 'https://example.com'],
        ],
        'expected' => [
          [
            'options' => [],
            'title' => 'Link title',
            'uri' => 'https://example.com',
          ],
        ],
      ],
      'A link with title and another with only URI' => [
        'values' => [
          'https://example.com/first',
          ['Second link', 'https://example.com/second'],
        ],
        'expected' => [
          [
            'options' => [],
            'uri' => 'https://example.com/first',
          ],
          [
            'options' => [],
            'title' => 'Second link',
            'uri' => 'https://example.com/second',
          ],
        ],
      ],
    ];
  }

}
