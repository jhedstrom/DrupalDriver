<?php

declare(strict_types=1);

namespace ConsumerProject\Driver;

use Drupal\Driver\Core\Core as BaseCore;

/**
 * Fixture: a consumer project's Core subclass.
 *
 * Lives outside the 'Drupal\Driver' namespace on purpose - 'DrupalDriver'
 * accepts any implementation of 'CoreInterface' via 'setCore()', so the
 * class name and namespace do not need to match the library's own lookup
 * chain. Registers consumer-owned field handlers by scanning its sibling
 * 'Field/' directory, the same mechanism the library's Core uses for its
 * own built-ins.
 */
class ConsumerCore extends BaseCore {

  /**
   * {@inheritdoc}
   */
  protected function registerDefaultFieldHandlers(): void {
    parent::registerDefaultFieldHandlers();
    $this->registerHandlersFromDirectory(__DIR__ . '/Field', __NAMESPACE__ . '\\Field');
  }

}
