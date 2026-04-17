<?php

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Driver\Fields\Drupal8\EntityReferenceHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the EntityReferenceHandler field handler.
 *
 * Full happy-path coverage requires a live Drupal kernel; these tests focus
 * on the helper logic and error paths that can be verified in isolation.
 */
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
  public function testExpandThrowsWhenNoEntityMatches() {
    $handler = $this->createHandler('node', []);
    $this->setUpEmptyQueryContainer('node');

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No entity 'Missing' of type 'node' exists.");

    $handler->expand(['Missing']);
  }

  /**
   * Tests getTargetBundles() returns configured bundles.
   */
  public function testGetTargetBundlesReturnsConfiguredBundles() {
    $handler = $this->createHandler('node', ['article', 'page']);

    $reflection = new \ReflectionMethod(EntityReferenceHandler::class, 'getTargetBundles');
    $reflection->setAccessible(TRUE);

    $this->assertSame(['article', 'page'], $reflection->invoke($handler));
  }

  /**
   * Tests getTargetBundles() returns NULL when none configured.
   */
  public function testGetTargetBundlesReturnsNullWhenEmpty() {
    $handler = $this->createHandler('node', []);

    $reflection = new \ReflectionMethod(EntityReferenceHandler::class, 'getTargetBundles');
    $reflection->setAccessible(TRUE);

    $this->assertNull($reflection->invoke($handler));
  }

  /**
   * Creates an EntityReferenceHandler with mocked fieldInfo and fieldConfig.
   */
  protected function createHandler($target_type, array $target_bundles) {
    $field_info = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getSetting'])
      ->getMock();
    $field_info->method('getSetting')
      ->with('target_type')
      ->willReturn($target_type);

    $handler_settings = $target_bundles !== [] ? ['target_bundles' => $target_bundles] : [];
    $field_config = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getSettings'])
      ->getMock();
    $field_config->method('getSettings')
      ->willReturn(['handler_settings' => $handler_settings]);

    $reflection = new \ReflectionClass(EntityReferenceHandler::class);
    $handler = $reflection->newInstanceWithoutConstructor();

    $info_property = new \ReflectionProperty(EntityReferenceHandler::class, 'fieldInfo');
    $info_property->setAccessible(TRUE);
    $info_property->setValue($handler, $field_info);

    $config_property = new \ReflectionProperty(EntityReferenceHandler::class, 'fieldConfig');
    $config_property->setAccessible(TRUE);
    $config_property->setValue($handler, $field_config);

    return $handler;
  }

  /**
   * Sets up a Drupal container whose queries always return no results.
   */
  protected function setUpEmptyQueryContainer($entity_type_id) {
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

      public function __construct(private $query) {}

      /**
       * Returns the injected entity query.
       */
      public function getQuery() {
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
