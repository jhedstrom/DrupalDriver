<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\AbstractCore;
use Drupal\Driver\Core\Field\AddressHandler;
use Drupal\Driver\Core\Field\DefaultHandler;
use Drupal\Driver\Core99\Field\FileHandler as Core99FileHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests AbstractCore::getFieldHandler() lookup chain.
 */
class FieldHandlerLookupTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpDrupalContainer();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * Tests that a version-specific handler is preferred when it exists.
   *
   * Core99\Field\FileHandler should be found first in the lookup chain
   * because 'file' => Core99\Field\FileHandler exists as a fixture.
   */
  public function testHandlerLookupPicksVersionOverride(): void {
    $core = new Core99TestCore(__DIR__, 'default');
    $entity = new \stdClass();

    $handler = $core->getFieldHandler($entity, 'node', 'field_file');

    $this->assertInstanceOf(Core99FileHandler::class, $handler);
    $this->assertSame('Core99\\Field\\FileHandler', $handler::MARKER);
  }

  /**
   * Tests that the default handler namespace is used when no override exists.
   *
   * 'address' has no Core99 fixture, so the chain falls back to
   * Core\Field\AddressHandler.
   */
  public function testHandlerLookupFallsBackToDefault(): void {
    $core = new Core99TestCore(__DIR__, 'default');
    $entity = new \stdClass();

    $handler = $core->getFieldHandler($entity, 'node', 'field_address');

    $this->assertInstanceOf(AddressHandler::class, $handler);
  }

  /**
   * Tests that unknown field types resolve to DefaultHandler.
   *
   * 'nonexistent_type' maps to no specific handler anywhere in the chain,
   * so Core\Field\DefaultHandler is used as the final fallback.
   */
  public function testHandlerLookupFallsBackToDefaultHandlerForUnknownType(): void {
    $core = new Core99TestCore(__DIR__, 'default');
    $entity = new \stdClass();

    $handler = $core->getFieldHandler($entity, 'node', 'field_unknown');

    $this->assertInstanceOf(DefaultHandler::class, $handler);
  }

  /**
   * Sets up a minimal Drupal container satisfying AbstractHandler::__construct.
   *
   * Provides 'entity_field.manager' and 'entity_type.manager' services with
   * enough stubbing to satisfy the field handler constructor without a full
   * Drupal bootstrap.
   */
  protected function setUpDrupalContainer(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getSettings')->willReturn([
      'field_overrides' => [],
      'available_countries' => ['AU' => 'Australia'],
    ]);

    $storage_definition = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage_definition->method('getType')->willReturn('string');

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_field_manager->method('getFieldStorageDefinitions')
      ->willReturn([
        'field_file' => $storage_definition,
        'field_address' => $storage_definition,
        'field_unknown' => $storage_definition,
      ]);
    $entity_field_manager->method('getFieldDefinitions')
      ->willReturn([
        'field_file' => $field_definition,
        'field_address' => $field_definition,
        'field_unknown' => $field_definition,
      ]);

    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getKey')->with('bundle')->willReturn('type');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')->willReturn($entity_type);

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $entity_field_manager);
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

}

/**
 * Testable AbstractCore subclass targeting Drupal version 99.
 *
 * Overrides 'getVersion()' to drive the lookup chain starting at 99, and
 * 'getEntityFieldTypes()' to return a static map of field name to type so
 * no Drupal bootstrap is required.
 */
class Core99TestCore extends AbstractCore {

  /**
   * {@inheritdoc}
   */
  protected function getVersion(): int {
    return 99;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldTypes(string $entity_type, array $base_fields = []): array {
    return [
      'field_file' => 'file',
      'field_address' => 'address',
      'field_unknown' => 'nonexistent_type',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap(): void {}

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {}

  /**
   * {@inheritdoc}
   */
  public function nodeCreate(\stdClass $node): object {
    return new \stdClass();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete(\stdClass $node): void {}

  /**
   * {@inheritdoc}
   */
  public function runCron(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user): void {}

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user): void {}

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, string $role_name): void {}

  /**
   * {@inheritdoc}
   */
  public function processBatch(): void {}

  /**
   * {@inheritdoc}
   */
  public function validateDrupalSite(): void {}

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions): int|string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete(string $role_name): void {}

  /**
   * {@inheritdoc}
   */
  public function termCreate(\stdClass $term): object {
    return new \stdClass();
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleList(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionPathList(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isField(string $entity_type, string $field_name): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField(string $entity_type, string $field_name): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function languageCreate(\stdClass $language): \stdClass|false {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete(\stdClass $language): void {}

  /**
   * {@inheritdoc}
   */
  public function configGet(string $name, string $key = ''): mixed {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function configGetOriginal(string $name, string $key = ''): mixed {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function configSet(string $name, string $key, mixed $value): void {}

  /**
   * {@inheritdoc}
   */
  public function entityCreate(string $entity_type, object $entity): EntityInterface {
    throw new \RuntimeException('Not implemented in test stub.');
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete(string $entity_type, object $entity): void {}

  /**
   * {@inheritdoc}
   */
  public function startCollectingMail(): void {}

  /**
   * {@inheritdoc}
   */
  public function stopCollectingMail(): void {}

  /**
   * {@inheritdoc}
   */
  public function getMail(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function clearMail(): void {}

  /**
   * {@inheritdoc}
   */
  public function sendMail(string $body, string $subject, string $to, string $langcode): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleInstall(string $module_name): void {}

  /**
   * {@inheritdoc}
   */
  public function moduleUninstall(string $module_name): void {}

}
