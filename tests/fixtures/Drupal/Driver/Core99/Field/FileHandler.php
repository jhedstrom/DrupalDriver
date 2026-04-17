<?php

declare(strict_types=1);

namespace Drupal\Driver\Core99\Field;

use Drupal\Driver\Core\Field\FileHandler as DefaultFileHandler;

/**
 * Fixture: simulated Core99-version-specific FileHandler override.
 *
 * Used by lookup-chain tests to verify that AbstractCore::getFieldHandler()
 * picks up version-specific handler overrides when they exist.
 */
class FileHandler extends DefaultFileHandler {

  /**
   * Marker so tests can identify which class was instantiated.
   */
  public const string MARKER = 'Core99\\Field\\FileHandler';

}
