<?php

declare(strict_types=1);

namespace Drupal\Driver\Alias;

use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Implements the 'CreationAliasCapabilityInterface' registry on a driver.
 *
 * Hosts an entity-type-indexed map of registered aliases, exposes the
 * 'getCreationAliases()' lookup, and provides two protected dispatcher
 * helpers for create methods to invoke: 'applyPreCreateAliases()' before
 * 'Entity::create()' and 'applyPostCreateAliases()' after save.
 *
 * Both dispatchers gate on 'EntityStubInterface::hasValue()' so an alias
 * only fires when the stub actually carries the key it owns.
 */
trait CreationAliasRegistryTrait {

  /**
   * Registered aliases indexed by entity type and alias name.
   *
   * @var array<string, array<string, \Drupal\Driver\Alias\CreationAliasInterface>>
   */
  protected array $creationAliases = [];

  /**
   * Adds an alias to the registry.
   *
   * Re-registering the same name on the same entity type replaces the
   * previous entry so subclasses can override aliases by simply
   * registering a new instance after 'parent::' setup.
   *
   * @param \Drupal\Driver\Alias\CreationAliasInterface $alias
   *   The alias to register.
   */
  public function registerCreationAlias(CreationAliasInterface $alias): void {
    $this->creationAliases[$alias->getEntityType()][$alias->getName()] = $alias;
  }

  /**
   * Returns creation aliases that apply to the given entity type.
   *
   * @param string $entity_type
   *   The Drupal entity type id.
   *
   * @return array<string, \Drupal\Driver\Alias\CreationAliasInterface>
   *   Map of alias name to alias instance. Empty array when no aliases apply.
   */
  public function getCreationAliases(string $entity_type): array {
    return $this->creationAliases[$entity_type] ?? [];
  }

  /**
   * Runs every pre-create alias whose key is present on the stub.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub being prepared for creation.
   * @param string $entity_type
   *   The Drupal entity type id whose aliases should run.
   */
  protected function applyPreCreateAliases(EntityStubInterface $stub, string $entity_type): void {
    foreach ($this->getCreationAliases($entity_type) as $alias) {
      if (!$alias instanceof PreCreateAliasInterface) {
        continue;
      }

      if (!$stub->hasValue($alias->getName())) {
        continue;
      }

      $alias->applyToStub($stub);
    }
  }

  /**
   * Runs every post-create alias whose key is present on the stub.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub used to create the entity.
   * @param object $entity
   *   The entity that was just saved.
   * @param string $entity_type
   *   The Drupal entity type id whose aliases should run.
   */
  protected function applyPostCreateAliases(EntityStubInterface $stub, object $entity, string $entity_type): void {
    foreach ($this->getCreationAliases($entity_type) as $alias) {
      if (!$alias instanceof PostCreateAliasInterface) {
        continue;
      }

      if (!$stub->hasValue($alias->getName())) {
        continue;
      }

      $alias->applyAfterCreate($stub, $entity);
    }
  }

}
