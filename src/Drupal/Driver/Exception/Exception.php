<?php

declare(strict_types=1);

namespace Drupal\Driver\Exception;

use Drupal\Driver\DriverInterface;

/**
 * Drupal driver manager base exception class.
 */
abstract class Exception extends \Exception {

  /**
   * The driver where the exception occurred.
   */
  private readonly ?DriverInterface $driver;

  /**
   * Initializes Drupal driver manager exception.
   *
   * @param string $message
   *   The exception message.
   * @param \Drupal\Driver\DriverInterface $driver
   *   The driver where the exception occurred.
   * @param int $code
   *   Optional exception code. Defaults to 0.
   * @param \Exception $previous
   *   Optional previous exception that was thrown.
   */
  public function __construct(string $message, ?DriverInterface $driver = NULL, int $code = 0, ?\Exception $previous = NULL) {
    $this->driver = $driver;
    parent::__construct($message, $code, $previous);
  }

  /**
   * Returns exception driver.
   *
   * @return \Drupal\Driver\DriverInterface|null
   *   The driver where the exception occurred, or NULL if not set.
   */
  public function getDriver(): ?DriverInterface {
    return $this->driver;
  }

}
