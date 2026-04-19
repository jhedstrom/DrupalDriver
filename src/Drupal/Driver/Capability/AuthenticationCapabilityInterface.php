<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: authenticate users on the backend.
 */
interface AuthenticationCapabilityInterface {

  /**
   * Logs a user in.
   *
   * @param \stdClass $user
   *   The user to log in.
   */
  public function login(\stdClass $user): void;

  /**
   * Logs the current user out.
   */
  public function logout(): void;

}
