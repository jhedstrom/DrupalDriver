<?php

declare(strict_types=1);

namespace Drupal\Driver\Hint;

use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Implements the 'CreationHintCapabilityInterface' registry on a driver.
 *
 * Hosts an entity-type-indexed map of registered hints, exposes the
 * 'getCreationHints()' lookup, and provides two protected dispatcher
 * helpers for create methods to invoke: 'applyPreCreateHints()' before
 * 'Entity::create()' and 'applyPostCreateHints()' after save.
 *
 * Both dispatchers gate on 'EntityStubInterface::hasValue()' so a hint
 * only fires when the stub actually carries the alias key it owns.
 */
trait CreationHintRegistryTrait {

  /**
   * Registered hints indexed by entity type and hint name.
   *
   * @var array<string, array<string, \Drupal\Driver\Hint\CreationHintInterface>>
   */
  protected array $creationHints = [];

  /**
   * Adds a hint to the registry.
   *
   * Re-registering the same name on the same entity type replaces the
   * previous entry so subclasses can override hints by simply registering
   * a new instance after 'parent::' setup.
   *
   * @param \Drupal\Driver\Hint\CreationHintInterface $hint
   *   The hint to register.
   */
  public function registerCreationHint(CreationHintInterface $hint): void {
    $this->creationHints[$hint->getEntityType()][$hint->getName()] = $hint;
  }

  /**
   * Returns creation hints that apply to the given entity type.
   *
   * @param string $entity_type
   *   The Drupal entity type id.
   *
   * @return array<string, \Drupal\Driver\Hint\CreationHintInterface>
   *   Map of hint name to hint instance. Empty array when no hints apply.
   */
  public function getCreationHints(string $entity_type): array {
    return $this->creationHints[$entity_type] ?? [];
  }

  /**
   * Runs every pre-create hint whose alias is present on the stub.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub being prepared for creation.
   * @param string $entity_type
   *   The Drupal entity type id whose hints should run.
   */
  protected function applyPreCreateHints(EntityStubInterface $stub, string $entity_type): void {
    foreach ($this->getCreationHints($entity_type) as $hint) {
      if (!$hint instanceof PreCreateHintInterface) {
        continue;
      }

      if (!$stub->hasValue($hint->getName())) {
        continue;
      }

      $hint->applyToStub($stub);
    }
  }

  /**
   * Runs every post-create hint whose alias is present on the stub.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub used to create the entity.
   * @param object $entity
   *   The entity that was just saved.
   * @param string $entity_type
   *   The Drupal entity type id whose hints should run.
   */
  protected function applyPostCreateHints(EntityStubInterface $stub, object $entity, string $entity_type): void {
    foreach ($this->getCreationHints($entity_type) as $hint) {
      if (!$hint instanceof PostCreateHintInterface) {
        continue;
      }

      if (!$stub->hasValue($hint->getName())) {
        continue;
      }

      $hint->applyAfterCreate($stub, $entity);
    }
  }

}
