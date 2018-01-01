<?php

namespace Drupal\Driver\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Driver\Exception\Exception;

/**
 * Provides the plugin manager for the Driver's field plugins.
 */
class DriverPluginManagerBase extends DefaultPluginManager {

  protected $driverPluginType;

  protected $matchedDefinitions;

  protected $filters;

  protected $specificityCriteria;

  /**
   * Constructor for DriverFieldPluginManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {

    // Add the driver to the namespaces searched for plugins.
    $reflection = new \ReflectionClass($this);
    $driverPath = dirname($reflection->getFileName(), 2);
    $namespaces = $namespaces->getArrayCopy();
    $supplementedNamespaces = new \ArrayObject();
    foreach ($namespaces as $name => $class) {
      $supplementedNamespaces[$name] = $class;
    }
    $supplementedNamespaces['Drupal\Driver'] = $driverPath;

    parent::__construct('Plugin/' . $this->getDriverPluginType(), $supplementedNamespaces, $module_handler,
      'Drupal\Driver\Plugin\\' .  $this->getDriverPluginType() . 'PluginInterface',
      'Drupal\Driver\Annotation\\' . $this->getDriverPluginType());

    $this->setCacheBackend($cache_backend, $this->getDriverPluginType() . '_plugins');

  }

  /**
   * Sorts plugin definitions into groups according to how well they fit
   * a target as specified by grouping criteria.
   */
  public function getMatchedDefinitions($rawTarget) {
    // Make sure the target is in a filterable format.
    $target = $this->getFilterableTarget($rawTarget);
    foreach ($this->getFilters() as $filter) {
      if (!isset($target[$filter])) {
        throw new \Exception("Plugin target is missing required filter property '" . $filter . "'.");
      }
    }

    // Get stored plugins if available.
    $targetKey = serialize($target);
    if (isset($this->MatchedDefinitions[$targetKey])) {
      return $this->MatchedDefinitions[$targetKey];
    }

    // Discover plugins & discard those that don't match the target.
    $definitions = $this->getDefinitions();
    $definitions = $this->filterDefinitionsByTarget($target, $definitions);

    // Group the plugins according to weight.
    $weighted_definitions = [];
    foreach ($definitions as $definition) {
      $weight = $definition['weight'];
      $weighted_definitions[$weight][] = $definition;
    }

    // Group by specificity within each weight group.
    $groupedDefinitions = [];
    foreach ($weighted_definitions as $weight => $weightGroup) {
      $groupedDefinitions[$weight] = $this->sortDefinitionsBySpecificity($weightGroup);
    }

    // Sort the weight groups high to low.
    krsort($groupedDefinitions);

    // Flatten the weight and specificity groups, while preserving sort order.
    if (count($groupedDefinitions) === 0) {
      $flattenedDefinitions = [];
    }
    else {
      $flattenedDefinitions = call_user_func_array('array_merge', $groupedDefinitions);
      $flattenedDefinitions = call_user_func_array('array_merge', $flattenedDefinitions);
    }

    $this->setMatchedDefinitions($targetKey, $flattenedDefinitions);
    return $this->matchedDefinitions[$targetKey];
  }

  /**
   * Convert a target object into a filterable target, an array with a key for
   * each filter.
   */
  protected function getFilterableTarget($rawTarget) {
    return $rawTarget;
  }

  /**
   * Sort an array of definitions by their specificity.
   */
  protected function sortDefinitionsBySpecificity($definitions) {
    // Group definitions by which criteria they match
    $groupedDefinitions = [];
    foreach($definitions as $definition) {
      $group = $this->findSpecificityGroup($definition);
      $groupedDefinitions[$group][] = $definition;
    }

    // Sort alphabetically by id within groups
    $sortedDefinitions = [];
    foreach ($groupedDefinitions as $groupName => $groupDefinitions) {
      usort($groupDefinitions, function ($a, $b) {
        return strcmp($a['id'], $b['id']);
      });
      $sortedDefinitions[$groupName] = $groupDefinitions;
    }

    // Sort groups by the order of the specificity criteria.
    ksort($sortedDefinitions);
    return $sortedDefinitions;
  }

  /**
   * Find the specificity group a plugin definition belongs to.
   */
  protected function findSpecificityGroup($definition) {
    // Work  though specificity criteria until a match is found.
    foreach ($this->getSpecificityCriteria() as $key => $criteria) {
      foreach ($criteria as $criterion) {
        if (!isset($definition[$criterion])) {
          continue(2);
        }
      }
        return $key;
      }

    // If it matched no criteria, it must be a catch-all plugin.
    return count($this->getSpecificityCriteria());

  }

  /**
   * Remove plugin definitions that don't fit a target according to filters.
   */
  protected function filterDefinitionsByTarget($target, $definitions) {
    $filters = $this->getFilters();
    $filteredDefinitions = [];
    foreach ($definitions as $definition) {
      reset($filters);
      foreach ($filters as $filter) {
        // If a definition doesn't contain the value specified by the target,
        // for this filter, then skip this definition and don't store it.
        $isCompatibleArray = isset($definition[$filter]) &&
          is_array($definition[$filter]) && (count($definition[$filter]) > 0);
        if ($isCompatibleArray &&
          !in_array($target[$filter], $definition[$filter], TRUE)) {
          continue(2);
        }
      }
      $filteredDefinitions[] = $definition;
    }
    return $filteredDefinitions;
  }

  /**
   * Finds plugin definitions. Overwrites the parent method to retain discovered
   * plugins with the provider 'driver'.
   *
   * @return array
   *   List of definitions to store in cache.
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    $this->alterDefinitions($definitions);
    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $provider = $this->extractProviderFromDefinition($plugin_definition);
      if ($provider && !in_array($provider, ['driver', 'core', 'component']) && !$this->providerExists($provider)) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  protected function getDriverPluginType() {
    return $this->driverPluginType;
  }

  protected function getSpecificityCriteria() {
    return $this->specificityCriteria;
  }

  protected function getFilters() {
    return $this->filters;
  }

  protected function setMatchedDefinitions($targetKey, $definitions) {
    $this->matchedDefinitions[$targetKey] = $definitions;
  }

}