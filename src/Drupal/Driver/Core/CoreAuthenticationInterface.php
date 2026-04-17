<?php

declare(strict_types=1);

namespace Drupal\Driver\Core;

/**
 * The core has the ability to directly authenticate users.
 */
interface CoreAuthenticationInterface {

  /**
   * Logs a user in.
   */
  public function login(\stdClass $user): void;

  /**
   * Logs a user out.
   */
  public function logout(): void;

}
