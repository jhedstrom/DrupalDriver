<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Driver\Core\Field\TaxonomyTermReferenceHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TaxonomyTermReferenceHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
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
  public function testExpandReturnsTermId(): void {
    $term = new class {

      /**
       * Returns the term entity ID.
       */
      public function id(): int {
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
  public function testExpandThrowsWhenTermNotFound(): void {
    $this->setUpStorageWithResult([]);

    $handler = $this->createHandler();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No term 'Unknown' exists.");

    $handler->expand(['Unknown']);
  }

  /**
   * Creates a handler that bypasses the parent constructor.
   */
  protected function createHandler(): TaxonomyTermReferenceHandler {
    $reflection = new \ReflectionClass(TaxonomyTermReferenceHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

  /**
   * Sets up a Drupal container that returns the supplied lookup results.
   *
   * @param array<string, mixed> $lookup
   *   Map of term names to entity arrays.
   */
  protected function setUpStorageWithResult(array $lookup): void {
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
