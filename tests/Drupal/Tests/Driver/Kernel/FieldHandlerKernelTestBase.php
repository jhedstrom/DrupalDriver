<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Driver\Core\Core;
use Drupal\entity_test\EntityTestHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for field handler kernel round-trip tests.
 *
 * Provides scaffolding (entity_test bundle, field attachment helper) and a
 * round-trip assertion that drives entity creation entirely through the Core
 * driver. Subclasses add the handler's declaring module to the module list
 * and implement test methods that:
 *   1. Call attachField() to declare the field under test.
 *   2. Call assertFieldRoundTripViaDriver() with the input value.
 *
 * The round-trip assertion compares the driver-mutated stdClass stub (which
 * holds whatever the handler emitted from expand()) against the reloaded
 * entity. No assertions are made against expect-specific expand() values;
 * that coverage belongs in per-handler unit tests.
 */
abstract class FieldHandlerKernelTestBase extends KernelTestBase {

  /**
   * Baseline modules every field handler kernel test needs.
   *
   * Subclasses redeclare $modules as [...self::BASE_MODULES, 'handler_module'].
   *
   * @var array<string>
   */
  protected const BASE_MODULES = [
    'system',
    'field',
    'entity_test',
    'user',
  ];

  /**
   * The entity type used to host test fields.
   */
  protected const ENTITY_TYPE = 'entity_test';

  /**
   * The bundle used to host test fields.
   */
  protected const BUNDLE = 'entity_test';

  /**
   * The driver under test.
   */
  protected Core $core;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema(self::ENTITY_TYPE);
    $this->installEntitySchema('user');
    $this->installConfig(['system']);

    // entity_test does not auto-register a default bundle in kernel tests.
    // Drupal 11.2+ provides EntityTestHelper::createBundle() and deprecates
    // the legacy procedural helper; older cores only have the function.
    if (class_exists(EntityTestHelper::class)) {
      EntityTestHelper::createBundle(self::BUNDLE);
    }
    else {
      entity_test_create_bundle(self::BUNDLE);
    }

    // Core::bootstrap() is NOT called: KernelTestBase has already booted the
    // kernel. We only need a Core instance to call the driver API methods on.
    $this->core = new Core($this->root);
  }

  /**
   * Attaches a field to the test bundle.
   *
   * @param string $field_name
   *   The machine name of the field.
   * @param string $type
   *   The field type (e.g. 'datetime', 'link', 'list_string').
   * @param array<string, mixed> $storage_settings
   *   Settings passed to FieldStorageConfig.
   * @param array<string, mixed> $field_settings
   *   Settings passed to FieldConfig.
   */
  protected function attachField(string $field_name, string $type, array $storage_settings = [], array $field_settings = []): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => self::ENTITY_TYPE,
      'type' => $type,
      'settings' => $storage_settings,
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => self::ENTITY_TYPE,
      'bundle' => self::BUNDLE,
      'settings' => $field_settings,
    ])->save();
  }

  /**
   * Drives entity creation through the driver and asserts field round-trip.
   *
   * Core::entityCreate mutates the passed stdClass so its field values reflect
   * whatever the handler emitted. This method iterates those post-expansion
   * values and asserts the reloaded entity holds the same data.
   *
   * For single-property scalar values, the assertion compares against the
   * main field column. For multi-property arrays (e.g. link.uri / link.title),
   * the assertion compares only the keys the test set, ignoring computed or
   * defaulted columns that the storage layer may populate.
   *
   * @param string $field_name
   *   The field to round-trip.
   * @param array<int, mixed> $values
   *   Field deltas. Each delta is either a scalar (for single-property fields)
   *   or an associative array (for multi-property fields).
   */
  protected function assertFieldRoundTripViaDriver(string $field_name, array $values): void {
    $stub = (object) [
      'type' => self::BUNDLE,
      'name' => 'test entity',
      $field_name => $values,
    ];

    $this->core->entityCreate(self::ENTITY_TYPE, $stub);

    $reloaded = \Drupal::entityTypeManager()
      ->getStorage(self::ENTITY_TYPE)
      ->loadUnchanged($stub->id);
    $this->assertInstanceOf(ContentEntityInterface::class, $reloaded);

    // Some handlers (e.g. ImageHandler) emit a flat associative array as
    // single-delta shorthand rather than a list of deltas. Normalise that
    // shape into a one-element list so the iteration below is uniform.
    $deltas = is_array($stub->$field_name) && !array_is_list($stub->$field_name)
      ? [$stub->$field_name]
      : $stub->$field_name;

    foreach ($deltas as $delta => $expected) {
      $item = $reloaded->get($field_name)->get($delta);
      $this->assertNotNull($item, sprintf('Field "%s" is missing delta %d.', $field_name, $delta));

      if (is_array($expected)) {
        $actual = array_intersect_key($item->getValue(), $expected);
        $this->assertEquals($expected, $actual, sprintf('Field "%s" delta %d did not round-trip.', $field_name, $delta));
      }
      else {
        // Scalar path: compare against the field's main column. Most fields
        // use 'value', but entity_reference uses 'target_id' and other types
        // may use a different key. Prefer 'value' if present, else fall back
        // to the first property returned by getValue(). Loose equality is
        // intentional: SQLite returns integer/float columns as strings.
        $raw = $item->getValue();
        $actual = $raw['value'] ?? reset($raw);
        $this->assertEquals($expected, $actual, sprintf('Field "%s" delta %d did not round-trip.', $field_name, $delta));
      }
    }
  }

}
