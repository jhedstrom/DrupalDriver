<?php

namespace Drupal\Driver\Wrapper\Entity;

use Drupal\Driver\Exception\Exception;
use Drupal\Driver\Plugin\DriverEntityPluginInterface;
use Drupal\Driver\Plugin\DriverEntityPluginManager;
use Drupal\Driver\Plugin\DriverPluginManagerInterface;
use Drupal\Driver\Wrapper\Field\DriverFieldInterface;
use Drupal\Driver\Wrapper\Field\DriverFieldDrupal8;
use Drupal\Component\Utility\DriverNameMatcher;

/**
 * A base class for a Driver entity object that holds information about a
 * Drupal entity.
 */
abstract class DriverEntityBase implements DriverEntityWrapperInterface
{

  /**
   * Entity type's machine name.
   *
   * @var string
   */
    protected $type;

  /**
   * Entity bundle's machine name.
   *
   * @var string
   */
    protected $bundle;

  /**
   * A driver entity plugin manager object.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginManagerInterface
   */
    protected $entityPluginManager;

  /**
   * A driver field plugin manager object.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginManagerInterface
   */
    protected $fieldPluginManager;

  /**
   * The directory to search for additional project-specific driver plugins.
   *
   * @var string
   */
    protected $projectPluginRoot;

  /**
   * The preliminary bundle-agnostic matched driver entity plugin.
   *
   * @var \Drupal\Driver\Plugin\DriverEntityPluginInterface
   */
    protected $provisionalPlugin;

  /**
   * The final bundle-specific matched driver entity plugin.
   *
   * @var \Drupal\Driver\Plugin\DriverEntityPluginInterface
   */
    protected $finalPlugin;

  /**
   * Constructs a driver entity wrapper object.
   *
   * @param string $type
   *   Machine name of the entity type.
   * @param string $bundle
   *   (optional) Machine name of the entity bundle.
   * @param \Drupal\Driver\Plugin\DriverPluginManagerInterface $entityPluginManager
   *   (optional) An driver entity plugin manager.
   * @param \Drupal\Driver\Plugin\DriverPluginManagerInterface $entityPluginManager
   *   (optional) An driver entity plugin manager.
   * @param string $projectPluginRoot
   *   The directory to search for additional project-specific driver plugins .
   */
    public function __construct(
        $type,
        $bundle = null,
        DriverPluginManagerInterface $entityPluginManager = null,
        DriverPluginManagerInterface $fieldPluginManager = null,
        $projectPluginRoot = null
    ) {

        $this->setEntityPluginManager($entityPluginManager, $projectPluginRoot);
        $this->fieldPluginManager = $fieldPluginManager;
        $this->projectPluginRoot = $projectPluginRoot;
        $this->setType($type);

        // Provisional plugin set before bundle as it's used in bundle validation.
        $this->setProvisionalPlugin($this->getPlugin());

        if (!empty($bundle)) {
            $this->setBundle($bundle);
            // Only set final plugin if bundle is known.
            $this->setFinalPlugin($this->getPlugin());
        }
    }

  /**
   * {@inheritdoc}
   */
    public function __call($name, $arguments)
    {
        // Forward unknown calls to the plugin.
        if ($this->hasFinalPlugin()) {
            return call_user_func_array([
            $this->getFinalPlugin(),
            $name,
            ], $arguments);
        }
        throw new \Exception("Method '$name' unknown on Driver entity wrapper and plugin not yet available.");
    }

  /**
   * {@inheritdoc}
   */
    public function __get($name)
    {
        // Forward unknown calls to the plugin.
        if ($this->hasFinalPlugin()) {
            return $this->getFinalPlugin()->$name;
        }
        throw new \Exception("Property '$name' unknown on Driver entity wrapper and plugin not yet available.");
    }

  /**
   * {@inheritdoc}
   */
    public function bundle()
    {
        // Default to entity type as bundle. This is used when the bundle is not
        // yet known, for example during DriverField processing of the bundle field.
        // If no bundle is supplied, this default is permanently set as the bundle
        // later by getFinalPlugin().
        if (is_null($this->bundle)) {
            return $this->getEntityTypeId();
        }
        return $this->bundle;
    }

  /**
   * {@inheritdoc}
   */
    public function delete()
    {
        $this->getEntity()->delete();
        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function getEntity()
    {
        return $this->getFinalPlugin()->getEntity();
    }

  /**
   * Get an entity plugin.
   *
   * This may or may not be bundle-specific, depending on whether the bundle is
   * known at this point.
   *
   * @return \Drupal\Driver\Plugin\DriverEntityPluginInterface
   *   An instantiated driver entity plugin object.
   */
    protected function getPlugin()
    {
        if (is_null($this->getEntityTypeId())) {
            throw new \Exception("Entity type is required to discover matched plugins.");
        }

        // Build the basic config for the plugin.
        $config = [
        'type' => $this->getEntityTypeId(),
        'bundle' => $this->bundle(),
        'projectPluginRoot' => $this->projectPluginRoot,
        'fieldPluginManager' => $this->fieldPluginManager,
        ];

        // Discover, instantiate and store plugin.
        $manager = $this->getFinalPluginManager();
        // Get only the highest priority matched plugin.
        $matchedDefinitions = $manager->getMatchedDefinitions($this);
        if (count($matchedDefinitions) === 0) {
            throw new \Exception("No matching DriverEntity plugins found.");
        }
        $topDefinition = $matchedDefinitions[0];
        $plugin = $manager->createInstance($topDefinition['id'], $config);
        if (!($plugin instanceof DriverEntityPluginInterface)) {
          throw new \Exception("DriverEntity plugin '" . $topDefinition['id'] . "' failed to instantiate.");
        }
        return $plugin;
    }

  /**
   * {@inheritdoc}
   */
    public function getFinalPlugin()
    {
        if (!$this->hasFinalPlugin()) {
            // Commit to default bundle if still using that.
            if ($this->isBundleMissing()) {
                $this->setBundle($this->bundle());
            }
            $this->setFinalPlugin($this->getPlugin());
        }
        if (!$this->hasFinalPlugin()) {
            throw new \Exception("Failed to discover or instantiate bundle-specific plugin.");
        }

        return $this->finalPlugin;
    }

  /**
   * {@inheritdoc}
   */
    public function getEntityTypeId()
    {
        return $this->type;
    }

  /**
   * {@inheritdoc}
   */
    public function id()
    {
        return $this->getFinalPlugin()->id();
    }

  /**
   * {@inheritdoc}
   */
    public function isNew()
    {
        if ($this->hasFinalPlugin()) {
            return $this->getFinalPlugin()->isNew();
        } else {
            return true;
        }
    }

  /**
   * {@inheritdoc}
   */
    public function label()
    {
        return $this->getFinalPlugin()->label();
    }

  /**
   * {@inheritdoc}
   */
    public function load($entityId)
    {
        if (!is_string($entityId) && !is_integer($entityId)) {
            throw new \Exception("Entity ID to be loaded must be string or integer.");
        }
        if ($this->hasFinalPlugin()) {
            $this->getFinalPlugin()->load($entityId);
        } else {
            $entity = $this->getProvisionalPlugin()->load($entityId);
            if ($this->isBundleMissing()) {
                $this->setBundle($entity->bundle());
            }
            $this->getFinalPlugin()->load($entityId);
        }
        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function reload()
    {
        $this->getFinalPlugin()->reload();
        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function save()
    {
        $this->getFinalPlugin()->save();
        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function set($identifier, $field)
    {
        $this->setFields([$identifier => $field]);
        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function setBundle($identifier)
    {
        if ($this->hasFinalPlugin()) {
            throw new \Exception("Cannot change entity bundle after final plugin discovery has taken place");
        }
        $this->bundle = $identifier;
        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function setFinalPlugin($plugin)
    {
        if ($this->hasFinalPlugin()) {
            throw new \Exception("Cannot change entity plugin without risk of data loss.");
        }
        $this->finalPlugin = $plugin;
        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function setFields($fields)
    {
        // We don't try to identify all the fields here - or even check that they
        // are all identifiable - because we want to pass everything on to the
        // plugin as raw as possible. But we must extract the bundle field (if the
        // bundle is not already known) as the bundle is used in plugin discovery.
        if ($this->isBundleMissing()) {
            $fields = $this->extractBundleField($fields);
        }
        $this->getFinalPlugin()->setFields($fields);
        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function url($rel = 'canonical', $options = [])
    {
        return $this->getFinalPlugin()->url($rel, $options);
    }

  /**
   * {@inheritdoc}
   */
    public function tearDown()
    {
        return $this->getFinalPlugin()->tearDown();
    }

  /**
   * Extract the bundle field from a set of fields, and store the bundle.
   *
   * @param array $fields
   *   An array of inputs that represent fields.
   *
   * @return array
   *   An array of inputs that represent fields, without the bundle field.
   */
    protected function extractBundleField($fields)
    {
        $bundleKey = $this->getProvisionalPlugin()->getBundleKey();
        // If this is a bundle-less entity, there's nothing to do.
        if (empty($bundleKey)) {
            return $fields;
        } else {
            // BC support for identifying the bundle by the name 'step_bundle'.
            if (isset($fields['step_bundle'])) {
              $fields[$bundleKey] = $fields['step_bundle'];
              unset($fields['step_bundle']);
            }
            // Find the bundle field, if it is present among the fields.
            $bundleKeyLabels = $this->getProvisionalPlugin()->getBundleKeyLabels();
            $candidates = [];
            foreach ($bundleKeyLabels as $label) {
                $candidates[$label] = $bundleKey;
            }
            $matcher = new DriverNameMatcher($candidates);
            $bundleFieldMatch = $matcher->identifySet($fields);

            // If the bundle field has been found, process it and set the bundle.
            // Don't throw an exception if none if found, as it is possible to have
            // entities (like entity_test) that have a bundle key but don't require
            // a bundle to be set.
            if (count($bundleFieldMatch) !== 0) {
                if ($bundleFieldMatch[$bundleKey] instanceof DriverFieldInterface) {
                    $bundleField = $bundleFieldMatch[$bundleKey];
                } else {
                    $bundleField = $this->getNewDriverField($bundleKey, $bundleFieldMatch[$bundleKey]);
                }
                $this->setBundle($bundleField->getProcessedValues()[0]['target_id']);
            }

            // Return the other fields (with the bundle field now removed).
            return $matcher->getUnmatchedTargets();
        }
    }

  /**
   * Get the driver entity plugin manager.
   *
   * @return \Drupal\Driver\Plugin\DriverPluginManagerInterface
   */
    protected function getFinalPluginManager()
    {
        return $this->entityPluginManager;
    }

  /**
   * Get a new driver field with values.
   *
   * @param string $fieldName
   *   A string identifying an entity field.
   * @param string|array $values
   *   An input that can be transformed into Driver field values.
   */
    protected function getNewDriverField($fieldName, $values)
    {
        $driverFieldVersionClass = "Drupal\Driver\Wrapper\Field\DriverFieldDrupal" . $this->version;
        $field = new $driverFieldVersionClass(
        $values,
        $fieldName,
        $this->getEntityTypeId(),
        $this->bundle(),
        $this->projectPluginRoot,
        $this->fieldPluginManager
        );
        return $field;
    }

  /**
   * Gets the provisional entity plugin.
   *
   * @return \Drupal\Driver\Plugin\DriverEntityPluginInterface
   */
    protected function getProvisionalPlugin()
    {
        if ($this->hasFinalPlugin()) {
            return $this->getFinalPlugin();
        }
        return $this->provisionalPlugin;
    }

  /**
   * Whether a matched plugin has yet been discovered and stored.
   *
   * @return boolean
   */
    protected function hasFinalPlugin()
    {
        $hasFinalPlugin = !is_null($this->finalPlugin);
        if ($hasFinalPlugin) {
            $hasFinalPlugin = $this->finalPlugin instanceof DriverEntityPluginInterface;
        }
        return $hasFinalPlugin;
    }

  /**
   * Whether a bundle has been set yet.
   *
   * @return boolean
   */
    protected function isBundleMissing()
    {
        $supportsBundles = $this->getProvisionalPlugin()->supportsBundles();
        return ($supportsBundles && is_null($this->bundle));
    }

  /**
   * Set the driver entity plugin manager.
   *
   * @param \Drupal\Driver\Plugin\DriverPluginManagerInterface $manager
   *   The driver entity plugin manager.
   * @param string $projectPluginRoot
   *   The directory to search for additional project-specific driver plugins.
   */
    protected function setEntityPluginManager($manager, $projectPluginRoot)
    {
        if (!($manager instanceof DriverPluginManagerInterface)) {
            $manager = new DriverEntityPluginManager($this->namespaces, $this->cache_backend, $this->module_handler, $this->version, $projectPluginRoot);
        }
        $this->entityPluginManager = $manager;
    }

  /**
   * Sets the provisional entity plugin.
   *
   * @param \Drupal\Driver\Plugin\DriverEntityPluginInterface
   */
    protected function setProvisionalPlugin($plugin)
    {
        $this->provisionalPlugin = $plugin;
    }
}
