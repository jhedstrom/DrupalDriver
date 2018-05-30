<?php

namespace Drupal\Driver\Wrapper\Entity;

use Drupal\Driver\Wrapper\Field\DriverFieldDrupal8;
use Drupal\Driver\Plugin\DriverPluginManagerInterface;
use Drupal\Driver\Plugin\DriverNameMatcher;

/**
 * A Driver wrapper for Drupal 8 entities.
 */
class DriverEntityDrupal8 extends DriverEntityBase implements DriverEntityWrapperInterface
{

  /**
   * The Drupal version being driven.
   *
   * @var integer
   */
    protected $version = 8;

    public function __construct(
        $type,
        $bundle = null,
        DriverPluginManagerInterface $entityPluginManager = null,
        DriverPluginManagerInterface $fieldPluginManager = null,
        $projectPluginRoot = null
    ) {
        // Set Drupal environment variables used by default plugin manager.
        $this->namespaces = \Drupal::service('container.namespaces');
        $this->cache_backend = $cache_backend = \Drupal::service('cache.discovery');
        $this->module_handler = $module_handler = \Drupal::service('module_handler');

        parent::__construct($type, $bundle, $entityPluginManager, $fieldPluginManager, $projectPluginRoot);
    }

  /**
   * {@inheritdoc}
   */
    public static function create($fields, $type, $bundle = null)
    {
        $entity = new DriverEntityDrupal8(
            $type,
            $bundle
        );
        $entity->setFields($fields);
        return $entity;
    }

  /**
   * {@inheritdoc}
   */
    public function setBundle($identifier)
    {
        // Don't set a bundle if the entity doesn't support bundles.
        $supportsBundles = $this->getProvisionalPlugin()->supportsBundles();
        if ($supportsBundles) {
            $bundles = $this->getProvisionalPlugin()->getBundles();
            $matcher = new DriverNameMatcher($bundles);
            $result = $matcher->identify($identifier);
            if (is_null($result)) {
                throw new \Exception("'$identifier' could not be identified as a bundle of the '" . $this->getEntityTypeId() . "' entity type.");
            }
            parent::setBundle($result);
        }
        return $this;
    }

  /**
   * Set the entity type.
   *
   * @param string $identifier
   *   A human-friendly name for an entity type .
   */
    protected function setType($identifier)
    {
        $typeDefinitions = \Drupal::EntityTypeManager()->getDefinitions();
        $candidates = [];
        foreach ($typeDefinitions as $machineName => $typeDefinition) {
            $label = (string) $typeDefinition->getLabel();
            $candidates[$label] = $machineName;
        }
        $matcher = new DriverNameMatcher($candidates);
        $result = $matcher->identify($identifier);
        if (is_null($result)) {
            throw new \Exception("'$identifier' could not be identified as an entity type.");
        }
        $this->type = $result;
    }
}
