<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Driver\Core\Core;
use Drupal\Driver\Core\Field\AbstractHandler;
use Drupal\Driver\Core\Field\AddressHandler;
use Drupal\Driver\Core\Field\DefaultHandler;
use Drupal\Driver\Entity\EntityStub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests field handler resolution against the registry.
 *
 * The registry replaced the class-name lookup chain: Core's constructor
 * calls 'registerDefaultFieldHandlers()' to populate built-in handlers, and
 * consumers override via 'registerFieldHandler()'. These tests cover the
 * three tiers: default (constructor-registered), consumer override, and
 * fallback to 'DefaultHandler' for unknown field types.
 *
 * @group core
 * @group fields
 */
#[Group('core')]
#[Group('fields')]
class CoreFieldHandlerLookupTest extends TestCase {

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
   * Tests that the constructor pre-registers the project's built-in handlers.
   */
  public function testConstructorRegistersBuiltInHandlers(): void {
    $core = new FieldTypeMapCore(__DIR__, 'default', ['field_address' => 'address']);

    $handler = $core->getFieldHandler(new EntityStub('node'), 'node', 'field_address');

    $this->assertInstanceOf(AddressHandler::class, $handler);
  }

  /**
   * Tests that a consumer registration wins over the built-in handler.
   */
  public function testConsumerRegistrationOverridesBuiltIn(): void {
    $core = new FieldTypeMapCore(__DIR__, 'default', ['field_address' => 'address']);
    $core->registerFieldHandler('address', CustomFieldHandler::class);

    $handler = $core->getFieldHandler(new EntityStub('node'), 'node', 'field_address');

    $this->assertInstanceOf(CustomFieldHandler::class, $handler);
  }

  /**
   * Tests that unknown field types fall back to 'DefaultHandler'.
   */
  public function testUnknownFieldTypeFallsBackToDefaultHandler(): void {
    $core = new FieldTypeMapCore(__DIR__, 'default', ['field_x' => 'nonexistent_type']);

    $handler = $core->getFieldHandler(new EntityStub('node'), 'node', 'field_x');

    $this->assertInstanceOf(DefaultHandler::class, $handler);
  }

  /**
   * Tests that 'getFieldHandler()' throws when the field is not resolvable.
   */
  public function testThrowsWhenFieldIsMissing(): void {
    $core = new FieldTypeMapCore(__DIR__, 'default', []);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/Field "field_missing" not found/');

    $core->getFieldHandler(new EntityStub('node'), 'node', 'field_missing');
  }

  /**
   * Tests that registering a non-handler class throws at registration time.
   *
   * Failing at registration - rather than at 'getFieldHandler()' resolution -
   * surfaces consumer typos immediately in test bootstrap rather than the
   * first time the affected field runs through expansion.
   */
  public function testRegisterRejectsNonHandlerClass(): void {
    $core = new FieldTypeMapCore(__DIR__, 'default', []);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/must implement/');

    $core->registerFieldHandler('phone', \stdClass::class);
  }

  /**
   * Tests that registering an abstract handler class throws at registration.
   *
   * 'AbstractHandler' satisfies 'is_subclass_of(... FieldHandlerInterface)'
   * but cannot be instantiated, so 'getFieldHandler()' would fatal with a
   * cryptic 'Cannot instantiate abstract class' error at the first call. The
   * registry-contract docblock on 'CoreInterface::registerFieldHandler()'
   * promises rejection at registration time - this test holds it to that.
   */
  public function testRegisterRejectsAbstractHandlerClass(): void {
    $core = new FieldTypeMapCore(__DIR__, 'default', []);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/must be instantiable/');

    $core->registerFieldHandler('phone', AbstractHandler::class);
  }

  /**
   * Sets up a minimal Drupal container satisfying AbstractHandler construction.
   *
   * AbstractHandler's constructor pulls the entity field manager and the
   * entity type manager off '\Drupal'; tests instantiate handlers via the
   * registry, so both services must resolve. Storage and field definitions
   * are stubbed generously because the tests don't care about their shape.
   */
  protected function setUpDrupalContainer(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getSettings')->willReturn([]);

    $storage_definition = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage_definition->method('getType')->willReturn('string');

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_field_manager->method('getFieldStorageDefinitions')
      ->willReturn([
        'field_address' => $storage_definition,
        'field_x' => $storage_definition,
      ]);
    $entity_field_manager->method('getFieldDefinitions')
      ->willReturn([
        'field_address' => $field_definition,
        'field_x' => $field_definition,
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
 * Test Core subclass returning a caller-supplied field-type map.
 *
 * Stubs only 'getEntityFieldTypes()' so the tests drive
 * 'getFieldHandler()' without a real Drupal bootstrap. Everything else
 * comes from 'Core', including the default field-handler registration
 * the constructor performs.
 */
class FieldTypeMapCore extends Core {

  /**
   * Constructs a Core instance that returns a supplied field-type map.
   *
   * @param string $drupal_root
   *   Drupal root directory.
   * @param string $uri
   *   Site URI.
   * @param array<string, string> $field_type_map
   *   Map of field name to field type id.
   */
  public function __construct(string $drupal_root, string $uri, protected array $field_type_map) {
    parent::__construct($drupal_root, $uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldTypes(string $entity_type, ?string $bundle = NULL): array {
    return $this->field_type_map;
  }

}

/**
 * Test handler used to verify consumer registrations win over defaults.
 */
class CustomFieldHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values): array {
    return (array) $values;
  }

}
