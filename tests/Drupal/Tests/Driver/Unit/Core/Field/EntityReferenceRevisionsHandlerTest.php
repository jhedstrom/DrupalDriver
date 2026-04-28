<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Driver\Core\Field\EntityReferenceRevisionsHandler;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EntityReferenceRevisionsHandler.
 *
 * The handler mirrors EntityReferenceHandler's label-to-id resolution and
 * additionally populates 'target_revision_id' with the current revision id.
 * These tests exercise both the scalar input branch and the extras-array
 * input branch against a mocked entity query + storage.
 *
 * @group fields
 */
#[Group('fields')]
class EntityReferenceRevisionsHandlerTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * Tests that a scalar label resolves to target_id plus target_revision_id.
   */
  public function testScalarLabelResolvesToTargetAndRevisionIds(): void {
    $this->setUpDrupalContainer(resolved_id: 42, revision_id: 7);

    $handler = $this->handlerUnderTest();
    $result = $handler->expand(['Paragraph A']);

    $this->assertSame([['target_id' => 42, 'target_revision_id' => 7]], $result);
  }

  /**
   * Tests that an extras-array input preserves extras and resolves the target.
   */
  public function testExtrasArrayInputPreservesExtras(): void {
    $this->setUpDrupalContainer(resolved_id: 42, revision_id: 7);

    $handler = $this->handlerUnderTest();
    $result = $handler->expand([
      ['target_id' => 'Paragraph A', 'extra' => 'keep-me'],
    ]);

    $this->assertSame([
      ['target_id' => 42, 'extra' => 'keep-me', 'target_revision_id' => 7],
    ], $result);
  }

  /**
   * Tests that the handler throws when the target label does not resolve.
   */
  public function testUnknownTargetThrows(): void {
    $this->setUpDrupalContainer(resolved_id: NULL, revision_id: NULL);

    $handler = $this->handlerUnderTest();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No entity 'Paragraph A' of type 'paragraph' exists.");

    $handler->expand(['Paragraph A']);
  }

  /**
   * Tests that a non-revisionable target yields a NULL revision id.
   */
  public function testNonRevisionableTargetYieldsNullRevisionId(): void {
    $this->setUpDrupalContainer(resolved_id: 42, revision_id: NULL, revisionable: FALSE);

    $handler = $this->handlerUnderTest();
    $result = $handler->expand(['Paragraph A']);

    $this->assertSame([['target_id' => 42, 'target_revision_id' => NULL]], $result);
  }

  /**
   * Instantiates the handler without invoking its parent constructor.
   *
   * Direct construction would bootstrap the field-storage validation in
   * AbstractHandler, which this test replaces with injected fakes via
   * reflection. Using reflection keeps the test focused on expand() output.
   */
  protected function handlerUnderTest(): EntityReferenceRevisionsHandler {
    $storage = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage->method('getSetting')->with('target_type')->willReturn('paragraph');
    $storage->method('getMainPropertyName')->willReturn('target_id');

    $config = $this->createMock(FieldDefinitionInterface::class);
    $config->method('getSettings')->willReturn([]);

    $reflection = new \ReflectionClass(EntityReferenceRevisionsHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();
    $info_prop = $reflection->getParentClass()->getProperty('fieldInfo');
    $info_prop->setValue($handler, $storage);
    $config_prop = $reflection->getParentClass()->getProperty('fieldConfig');
    $config_prop->setValue($handler, $config);

    return $handler;
  }

  /**
   * Sets up the Drupal container with stubs the handler consults.
   *
   * The handler calls '\Drupal::entityTypeManager()' and
   * '\Drupal::entityQuery()', both resolved through '\Drupal::getContainer()'.
   * Wire the container so the query returns a deterministic id (or an empty
   * array to force the "not found" branch) and storage returns an optionally
   * revisionable target.
   */
  protected function setUpDrupalContainer(?int $resolved_id, ?int $revision_id, bool $revisionable = TRUE): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('orConditionGroup')->willReturn($query);
    $query->method('execute')->willReturn($resolved_id === NULL ? [] : [$resolved_id => $resolved_id]);

    if ($revisionable) {
      $target = $this->createMock(RevisionableInterface::class);
      $target->method('getRevisionId')->willReturn($revision_id);
    }
    else {
      $target = $this->createMock(EntityTypeInterface::class);
    }

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('load')->willReturn($target);
    $entity_storage->method('getQuery')->willReturn($query);

    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getKey')->willReturnMap([
      ['id', 'id'],
      ['label', 'label'],
      ['bundle', 'type'],
    ]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')->with('paragraph')->willReturn($entity_type);
    $entity_type_manager->method('getStorage')->with('paragraph')->willReturn($entity_storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

}
