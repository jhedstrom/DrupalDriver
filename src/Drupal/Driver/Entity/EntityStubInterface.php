<?php

declare(strict_types=1);

namespace Drupal\Driver\Entity;

/**
 * Contract for the typed envelope used during entity create/cleanup flows.
 *
 * Holds the desired-state values before save, the saved Drupal entity once
 * the driver populates it, and the bundle context the field-handler
 * pipeline needs for storage resolution.
 */
interface EntityStubInterface {

  /**
   * Default bundle key for entity types that do not declare one.
   *
   * Drupal Core's most common bundle key is 'type' (used by 'node',
   * 'block_content', 'entity_test', and others), so it is the fallback when
   * the caller has not specified a bundle key.
   */
  public const string DEFAULT_BUNDLE_KEY = 'type';

  /**
   * Returns the entity type ID (e.g. 'node', 'taxonomy_term', 'user').
   */
  public function getEntityType(): string;

  /**
   * Returns the bundle ID, or NULL for entity types without bundles.
   */
  public function getBundle(): ?string;

  /**
   * Returns the bundle key for this stub's entity type.
   */
  public function getBundleKey(): string;

  /**
   * Sets the bundle key for this stub's entity type.
   *
   * Callers that bootstrap a stub before the driver has a chance to consult
   * Drupal's entity type definitions can override the default here. The
   * driver itself sets this from the entity type's declared bundle key
   * before storage operations run.
   */
  public function setBundleKey(string $bundle_key): self;

  /**
   * Returns a single value, or the supplied default when absent.
   *
   * @param string $key
   *   The value name.
   * @param mixed $default
   *   Returned when the key is not in the bag.
   *
   * @return mixed
   *   The stored value or the default.
   */
  public function getValue(string $key, mixed $default = NULL): mixed;

  /**
   * Sets a single value.
   *
   * @param string $key
   *   The value name.
   * @param mixed $value
   *   The value to store.
   */
  public function setValue(string $key, mixed $value): self;

  /**
   * Returns TRUE when a key is set, even if its value is NULL.
   */
  public function hasValue(string $key): bool;

  /**
   * Removes a single value from the bag.
   */
  public function removeValue(string $key): self;

  /**
   * Returns the entire values bag.
   *
   * @return array<string, mixed>
   *   Stored values keyed by name.
   */
  public function getValues(): array;

  /**
   * Replaces the entire values bag.
   *
   * @param array<string, mixed> $values
   *   Replacement values keyed by name.
   */
  public function setValues(array $values): self;

  /**
   * Returns TRUE once the driver has called 'markSaved()'.
   */
  public function isSaved(): bool;

  /**
   * Records the saved Drupal entity object on the stub.
   *
   * @param object $entity
   *   The Drupal entity that was just persisted.
   */
  public function markSaved(object $entity): self;

  /**
   * Returns the saved Drupal entity object.
   *
   * @return object
   *   The entity supplied to 'markSaved()'.
   *
   * @throws \LogicException
   *   When 'markSaved()' has not yet been called.
   */
  public function getSavedEntity(): object;

  /**
   * Returns the saved entity's id, or NULL when the stub is unsaved.
   *
   * @return int|string|null
   *   The id resolved by the saved entity, or NULL.
   */
  public function getId(): int|string|null;

}
