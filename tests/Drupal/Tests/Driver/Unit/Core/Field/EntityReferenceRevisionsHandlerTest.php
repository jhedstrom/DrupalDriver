<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use PHPUnit\Framework\MockObject\MockObject;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\EntityReferenceRevisionsHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the EntityReferenceRevisionsHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class EntityReferenceRevisionsHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * Label -> id index for the entity query stub.
   *
   * @var array<string, int>
   */
  protected const KNOWN_LABELS = [
    'Paragraph A' => 42,
  ];

  /**
   * Revision id every loaded target reports.
   */
  protected const REVISION_ID = 7;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->createEntityTypeManager(self::KNOWN_LABELS, self::REVISION_ID));
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getSetting')
      ->with('target_type')
      ->willReturn('paragraph');

    $field_config = $this->createMock(FieldDefinitionInterface::class);
    $field_config->method('getSettings')->willReturn([]);

    $reflection = new \ReflectionClass(EntityReferenceRevisionsHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $info_property = new \ReflectionProperty(EntityReferenceRevisionsHandler::class, 'fieldInfo');
    $info_property->setValue($handler, $field_info);

    $config_property = new \ReflectionProperty(EntityReferenceRevisionsHandler::class, 'fieldConfig');
    $config_property->setValue($handler, $field_config);

    $main_property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_property->setValue($handler, 'target_id');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'bare label resolves to id and revision id' => [
      'Paragraph A',
      [['target_id' => 42, 'target_revision_id' => self::REVISION_ID]],
      NULL,
      NULL,
    ];
    yield 'record preserves extras and resolves target' => [
      [['target_id' => 'Paragraph A', 'extra' => 'keep-me']],
      [['target_id' => 42, 'extra' => 'keep-me', 'target_revision_id' => self::REVISION_ID]],
      NULL,
      NULL,
    ];
    yield 'integer id bypasses validation query' => [
      [99],
      [['target_id' => 99, 'target_revision_id' => self::REVISION_ID]],
      NULL,
      NULL,
    ];

    yield 'unknown label throws' => [
      ['Paragraph X'],
      NULL,
      \Exception::class,
      "No entity 'Paragraph X' of type 'paragraph' exists.",
    ];
  }

  /**
   * Builds the entity_type.manager + query + storage stubs.
   *
   * @param array<string, int> $known_labels
   *   Label-to-id index.
   * @param int $revision_id
   *   Revision id every loaded target reports.
   */
  protected function createEntityTypeManager(array $known_labels, int $revision_id): object {
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getKey')->willReturnMap([
      ['id', 'id'],
      ['label', 'label'],
      ['bundle', 'type'],
    ]);

    $target = $this->createMock(RevisionableInterface::class);
    $target->method('getRevisionId')->willReturn($revision_id);

    $query = $this->createQueryStub($known_labels);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('load')->willReturn($target);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')->willReturn($entity_type);
    $entity_type_manager->method('getStorage')->willReturn($storage);

    return $entity_type_manager;
  }

  /**
   * Builds an entity query mock backed by the label-to-id index.
   *
   * @param array<string, int> $known_labels
   *   Label-to-id index.
   */
  protected function createQueryStub(array $known_labels): QueryInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('orConditionGroup')->willReturnSelf();

    $captured_label = NULL;
    $query->method('condition')
      ->willReturnCallback(function (mixed $field, mixed $value = NULL) use ($query, &$captured_label): MockObject {
        if (is_string($field) && in_array($field, ['name', 'title', 'label'], TRUE) && $value !== NULL) {
          $captured_label = (string) $value;
        }

        return $query;
      });

    $query->method('execute')
      ->willReturnCallback(function () use (&$captured_label, $known_labels): array {
        return $captured_label !== NULL && isset($known_labels[$captured_label])
          ? [$known_labels[$captured_label]]
          : [];
      });

    return $query;
  }

}
