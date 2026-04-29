<?php

declare(strict_types=1);

namespace Drupal\Driver\Entity;

/**
 * Typed envelope for creating, tracking, and cleaning up a Drupal entity.
 *
 * Replaces the historical 'stdClass' that flowed between the extension's
 * Gherkin parser and the driver's create methods. Mirrors Drupal Core's own
 * 'Entity::create($type, $values)' shape - one final class, no subclasses,
 * with the entity type and bundle pinned at construction time and a
 * mutable values bag plus a saved-entity slot.
 */
final class EntityStub implements EntityStubInterface {

  /**
   * The saved Drupal entity object. NULL until the driver populates it.
   */
  protected ?object $entity = NULL;

  /**
   * The bundle key for this entity type ('type', 'vid', 'bundle', etc.).
   */
  protected string $bundleKey = self::DEFAULT_BUNDLE_KEY;

  /**
   * Set up the stub.
   *
   * @param string $entityType
   *   The entity type ID (e.g. 'node', 'taxonomy_term', 'user').
   * @param string|null $bundle
   *   The bundle ID (e.g. 'article'). NULL for entity types without bundles.
   * @param array<string, mixed> $values
   *   Flat map of base properties and field values, keyed by name.
   */
  public function __construct(
    protected readonly string $entityType,
    protected readonly ?string $bundle = NULL,
    protected array $values = [],
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle(): ?string {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleKey(): string {
    return $this->bundleKey;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundleKey(string $bundle_key): self {
    $this->bundleKey = $bundle_key;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(string $key, mixed $default = NULL): mixed {
    return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue(string $key, mixed $value): self {
    $this->values[$key] = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValue(string $key): bool {
    return array_key_exists($key, $this->values);
  }

  /**
   * {@inheritdoc}
   */
  public function removeValue(string $key): self {
    unset($this->values[$key]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValues(): array {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $values): self {
    $this->values = $values;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSaved(): bool {
    return $this->entity !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function markSaved(object $entity): self {
    $this->entity = $entity;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSavedEntity(): object {
    if ($this->entity === NULL) {
      throw new \LogicException(sprintf('EntityStub for "%s" has not been saved yet.', $this->entityType));
    }

    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): int|string|null {
    if ($this->entity === NULL) {
      return NULL;
    }

    return method_exists($this->entity, 'id') ? $this->entity->id() : NULL;
  }

}
