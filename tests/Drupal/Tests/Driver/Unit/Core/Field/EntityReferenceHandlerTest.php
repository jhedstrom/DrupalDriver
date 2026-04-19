<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Field\EntityReferenceHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the EntityReferenceHandler field handler.
 *
 * Full happy-path coverage requires a live Drupal kernel; these tests focus
 * on the helper logic and error paths that can be verified in isolation.
 *
 * @group fields
 */
#[Group('fields')]
class EntityReferenceHandlerTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * Tests that unknown values raise an exception.
   */
  public function testExpandThrowsWhenNoEntityMatches(): void {
    $handler = $this->createHandler('node', []);
    $this->setUpEmptyQueryContainer('node');

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No entity 'Missing' of type 'node' exists.");

    $handler->expand(['Missing']);
  }

  /**
   * Tests getTargetBundles() returns configured bundles.
   */
  public function testGetTargetBundlesReturnsConfiguredBundles(): void {
    $handler = $this->createHandler('node', ['article', 'page']);

    $reflection = new \ReflectionMethod(EntityReferenceHandler::class, 'getTargetBundles');

    $this->assertSame(['article', 'page'], $reflection->invoke($handler));
  }

  /**
   * Tests getTargetBundles() returns NULL when none configured.
   */
  public function testGetTargetBundlesReturnsNullWhenEmpty(): void {
    $handler = $this->createHandler('node', []);

    $reflection = new \ReflectionMethod(EntityReferenceHandler::class, 'getTargetBundles');

    $this->assertNull($reflection->invoke($handler));
  }

  /**
   * Creates an EntityReferenceHandler with mocked fieldInfo and fieldConfig.
   *
   * @param string $target_type
   *   The target entity type ID.
   * @param array<string, string> $target_bundles
   *   Target bundle restrictions.
   *
   * @return \Drupal\Driver\Core\Field\EntityReferenceHandler
   *   A handler instance with fieldInfo and fieldConfig populated.
   */
  protected function createHandler(string $target_type, array $target_bundles = []): EntityReferenceHandler {
    $field_info = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_info->method('getSetting')
      ->with('target_type')
      ->willReturn($target_type);

    $handler_settings = $target_bundles !== [] ? ['target_bundles' => $target_bundles] : [];
    $field_config = $this->createMock(FieldDefinitionInterface::class);
    $field_config->method('getSettings')
      ->willReturn(['handler_settings' => $handler_settings]);

    $reflection = new \ReflectionClass(EntityReferenceHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $info_property = new \ReflectionProperty(EntityReferenceHandler::class, 'fieldInfo');
    $info_property->setValue($handler, $field_info);

    $config_property = new \ReflectionProperty(EntityReferenceHandler::class, 'fieldConfig');
    $config_property->setValue($handler, $field_config);

    return $handler;
  }

  /**
   * Sets up a Drupal container whose queries always return no results.
   */
  protected function setUpEmptyQueryContainer(string $entity_type_id): void {
    $definition = $this->createMock(EntityTypeInterface::class);
    $definition->method('getKey')->willReturnMap([
      ['id', 'nid'],
      ['label', 'title'],
      ['bundle', 'type'],
    ]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('orConditionGroup')->willReturn($query);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = new class($query) {

      public function __construct(private readonly QueryInterface $query) {}

      /**
       * Returns the injected entity query.
       */
      public function getQuery(): QueryInterface {
        return $this->query;
      }

    };

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')->willReturn($definition);
    $entity_type_manager->method('getStorage')->willReturn($storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

}
