<?php

namespace Drupal\Driver\Wrapper\Field;

use Drupal\Driver\Plugin\DriverPluginManagerInterface;
use Drupal\Driver\Plugin\DriverFieldPluginManager;

/**
 * A base class for a Driver field object that holds information about a Drupal
 * entity field.
 */
abstract class DriverFieldBase implements DriverFieldInterface
{

  /**
   * Human-readable text intended to identify the field instance.
   *
   * @var string
   */
    protected $identifier;

  /**
   * Field instance's machine name.
   *
   * @var string
   */
    protected $name;

  /**
   * Entity type.
   *
   * @var string
   */
    protected $entityType;

  /**
   * Entity bundle.
   *
   * @var string
   */
    protected $bundle;

  /**
   * Raw field values before processing by DriverField plugins.
   *
   * @var array
   */
    protected $rawValues;

  /**
   * Field values after processing by DriverField plugins.
   *
   * @var array
   */
    protected $processedValues;

  /**
   * A driver field plugin manager object.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginManagerInterface
   */
    protected $fieldPluginManager;

  /**
   * Directory to search for additional project-specific driver plugins.
   *
   * @var string
   */
    protected $projectPluginRoot;

  /**
   * Construct a DriverField object.
   *
   * @param mixed
   *   Raw values for the field. Typically an array, one for each value of a
   *   multivalue field, but can be single. Values are typically string.
   * @param string $fieldName
   *   The machine name of the field.
   * @param string $entityType
   *   The machine name of the entity type the field is attached to.
   * @param string $bundle
   *   (optional) The machine name of the entity bundle the field is attached to.
   * @param string $projectPluginRoot
   *   The directory to search for additional project-specific driver plugins.
   * @param NULL|\Drupal\Driver\Plugin\DriverPluginManagerInterface $fieldPluginManager
   *   (optional) A driver field plugin manager.
   *
   */
    public function __construct(
        $rawValues,
        $identifier,
        $entityType,
        $bundle = null,
        $projectPluginRoot = null,
        $fieldPluginManager = null
    ) {

        // Default to entity type as bundle if no bundle specified.
        if (empty($bundle)) {
            $bundle = $entityType;
        }
        // Wrap single values into an array so single and multivalue fields can be
        // handled identically.
        if (!is_array($rawValues)) {
            $rawValues = [$rawValues];
        }
        $this->projectPluginRoot = $projectPluginRoot;
        $this->setFieldPluginManager($fieldPluginManager, $projectPluginRoot);
        $this->rawValues = $rawValues;
        $this->entityType = $entityType;
        $this->bundle = $bundle;
        $this->name = $this->identify($identifier);
        $this->identifier = $identifier;
    }


  /**
   * {@inheritdoc}
   */
    public function getBundle()
    {
        return $this->bundle;
    }

  /**
   * {@inheritdoc}
   */
    public function getEntityType()
    {
        return $this->entityType;
    }

  /**
   * {@inheritdoc}
   */
    public function getName()
    {
        return $this->name;
    }

  /**
   * {@inheritdoc}
   */
    public function getProcessedValues()
    {
        if (is_null($this->processedValues)) {
            $this->setProcessedValues($this->getRawValues());
            $fieldPluginManager = $this->getFieldPluginManager();
            $definitions = $fieldPluginManager->getMatchedDefinitions($this);
            // Process values through matched plugins, until a plugin
            // declares it is the final one.
            foreach ($definitions as $definition) {
                $plugin = $fieldPluginManager->createInstance($definition['id'], ['field' => $this]);
                $processedValues = $plugin->processValues($this->processedValues);
                if (!is_array($processedValues)) {
                    throw new \Exception("Field plugin failed to return array of processed values.");
                }
                $this->setProcessedValues($processedValues);
                if ($plugin->isFinal($this)) {
                    break;
                };
            }
        }

        // Don't pass an array back to singleton config properties.
        if ($this->isConfigProperty()) {
            if ($this->getType() !== 'sequence') {
                if (count($this->processedValues) > 1) {
                    throw new \Exception("Config properties not of the type sequence should not have array input.");
                }
                return $this->processedValues[0];
            }
        }
        return $this->processedValues;
    }

  /**
   * {@inheritdoc}
   */
    public function getProjectPluginRoot()
    {
        return $this->projectPluginRoot;
    }

  /**
   * {@inheritdoc}
   */
    public function getRawValues()
    {
        return $this->rawValues;
    }

  /**
   * Sets the processed values.
   *
   * @return \Drupal\Driver\Plugin\DriverPluginManagerInterface
   *   The field plugin manager.
   */
    protected function getFieldPluginManager()
    {
        return $this->fieldPluginManager;
    }

  /**
   * {@inheritdoc}
   */
    public function isConfigProperty()
    {
        return false;
    }

  /**
   * Sets the processed values.
   *
   * @param array $values
   *   An array of processed field value sets.
   */
    protected function setProcessedValues(array $values)
    {
        $this->processedValues = $values;
    }

  /**
   * Set the driver field plugin manager.
   *
   * @param \Drupal\Driver\Plugin\DriverPluginManagerInterface $manager
   *   The driver entity plugin manager.
   * @param string $projectPluginRoot
   *   The directory to search for additional project-specific driver plugins.
   */
    protected function setFieldPluginManager($manager, $projectPluginRoot)
    {
        if (!($manager instanceof DriverPluginManagerInterface)) {
            $manager = new DriverFieldPluginManager($this->namespaces, $this->cache_backend, $this->module_handler, $this->version, $projectPluginRoot);
        }
        $this->fieldPluginManager = $manager;
    }
}
