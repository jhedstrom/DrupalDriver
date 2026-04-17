<?php

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Driver\Fields\Drupal8\TaxonomyTermReferenceHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the TaxonomyTermReferenceHandler field handler.
 */
class TaxonomyTermReferenceHandlerTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * Tests that a matching term returns its ID.
   */
  public function testExpandReturnsTermId() {
    $term = new class {

      /**
       * Returns the term entity ID.
       */
      public function id() {
        return 17;
      }

    };

    $this->setUpStorageWithResult(['Tag A' => [$term]]);

    $handler = $this->createHandler();

    $this->assertSame([17], $handler->expand(['Tag A']));
  }

  /**
   * Tests that an unknown term name raises an exception.
   */
  public function testExpandThrowsWhenTermNotFound() {
    $this->setUpStorageWithResult([]);

    $handler = $this->createHandler();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No term 'Unknown' exists.");

    $handler->expand(['Unknown']);
  }

  /**
   * Creates a handler that bypasses the parent constructor.
   */
  protected function createHandler() {
    $reflection = new \ReflectionClass(TaxonomyTermReferenceHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

  /**
   * Sets up a Drupal container that returns the supplied lookup results.
   */
  protected function setUpStorageWithResult(array $lookup) {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->willReturnCallback(fn($properties) => $lookup[$properties['name']] ?? []);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

}
