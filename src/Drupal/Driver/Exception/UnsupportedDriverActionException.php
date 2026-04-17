<?php

declare(strict_types=1);

namespace Drupal\Driver\Exception;

use Drupal\Driver\DriverInterface;

/**
 * Unsupported driver action.
 */
class UnsupportedDriverActionException extends Exception {

  /**
   * Initializes exception.
   *
   * @param string $template
   *   What is unsupported?
   * @param \Drupal\Driver\DriverInterface $driver
   *   Driver instance.
   * @param int $code
   *   The exception code.
   * @param \Exception $previous
   *   Previous exception.
   */
  public function __construct($template, DriverInterface $driver, $code = 0, ?\Exception $previous = NULL) {
    $message = sprintf($template, $driver::class);

    parent::__construct($message, $driver, $code, $previous);
  }

}
