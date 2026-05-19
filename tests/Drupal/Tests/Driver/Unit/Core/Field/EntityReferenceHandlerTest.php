<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use PHPUnit\Framework\MockObject\MockObject;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\EntityReferenceHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the EntityReferenceHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class EntityReferenceHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * Label -> id lookup the entity query stub returns matches against.
   *
   * @var array<string, int>
   */
  protected const KNOWN_LABELS = [
    'alice' => 7,
    'bob' => 8,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->createEntityTypeManager(self::KNOWN_LABELS));
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
      ->willReturn('user');

    $field_config = $this->createMock(FieldDefinitionInterface::class);
    $field_config->method('getSettings')->willReturn(['handler_settings' => []]);

    $reflection = new \ReflectionClass(EntityReferenceHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $info_property = new \ReflectionProperty(EntityReferenceHandler::class, 'fieldInfo');
    $info_property->setValue($handler, $field_info);

    $config_property = new \ReflectionProperty(EntityReferenceHandler::class, 'fieldConfig');
    $config_property->setValue($handler, $field_config);

    $main_property = new \ReflectionProperty(AbstractHandler::class, 'mainProperty');
    $main_property->setValue($handler, 'target_id');

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'bare label resolves to id' => [
      'alice',
      [['target_id' => 7]],
      NULL,
      NULL,
    ];
    yield 'list of one label' => [
      ['alice'],
      [['target_id' => 7]],
      NULL,
      NULL,
    ];
    yield 'list of multiple labels' => [
      ['alice', 'bob'],
      [['target_id' => 7], ['target_id' => 8]],
      NULL,
      NULL,
    ];
    yield 'record with target_id label' => [
      [['target_id' => 'alice']],
      [['target_id' => 7]],
      NULL,
      NULL,
    ];
    yield 'record preserves extras' => [
      [['target_id' => 'alice', 'display' => 1]],
      [['target_id' => 7, 'display' => 1]],
      NULL,
      NULL,
    ];
    yield 'integer id bypasses validation query' => [
      [42],
      [['target_id' => 42]],
      NULL,
      NULL,
    ];

    yield 'unknown label throws' => [
      ['nobody'],
      NULL,
      \Exception::class,
      "No entity 'nobody' of type 'user' exists.",
    ];
    yield 'mixed positional and named keys rejected' => [
      ['alice', 'extra' => 'oops'],
      NULL,
      \InvalidArgumentException::class,
      'Field value cannot mix positional and named keys',
    ];
    yield 'record missing main property rejected' => [
      ['display' => 1],
      NULL,
      \InvalidArgumentException::class,
      'Field record must include the main property "target_id"',
    ];
  }

  /**
   * Builds an entity_type.manager + entity-query stub keyed by label.
   *
   * @param array<string, int> $known_labels
   *   Label-to-id index the entity query returns matches from.
   */
  protected function createEntityTypeManager(array $known_labels): object {
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getKey')->willReturnMap([
      ['id', 'uid'],
      ['label', 'name'],
      ['bundle', FALSE],
    ]);

    $query = $this->createQueryStub($known_labels);
    $storage = $this->createStorageWithQuery($query);

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

  /**
   * Builds an entity storage stub whose 'getQuery()' returns the given query.
   */
  protected function createStorageWithQuery(QueryInterface $query): object {
    return new class($query) {

      public function __construct(protected readonly QueryInterface $query) {}

      /**
       * Returns the injected entity query.
       */
      public function getQuery(): QueryInterface {
        return $this->query;
      }

    };
  }

}
