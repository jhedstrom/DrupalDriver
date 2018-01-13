<?php

namespace Drupal\Driver\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for Driver field plugins.
 */
class DriverFieldPluginDrupal8Base extends DriverFieldPluginBase implements DriverFieldPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected function getMainPropertyName() {
    if ($this->field->isConfigProperty()) {
      throw new \Exception("Main property name not used when processing config properties.");
    }
    return $this->field->getStorageDefinition()->getMainPropertyName();
  }

}