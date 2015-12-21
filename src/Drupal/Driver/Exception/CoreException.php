<?php

/**
 * @file
 * Contains \Drupal\Driver\Exception\CoreException.
 */

namespace Drupal\Driver\Exception;

/**
 * Base class for exceptions thrown in classes implementing CoreInterface.
 */
abstract class CoreException extends Exception {

  /**
   * Initializes exception.
   *
   * @param string $message
   *   The exception message.
   * @param int $code
   *   Optional exception code. Defaults to 0.
   * @param \Exception $previous
   *   Optional previous exception that was thrown.
   */
  public function __construct($message, $code = 0, \Exception $previous = NULL) {
    parent::__construct($message, NULL, $code, $previous);
  }

}
