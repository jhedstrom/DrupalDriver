<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Fixtures;

/**
 * Test double for the user object returned by user-lookup closures.
 *
 * Exposes the single 'id()' accessor that hint resolvers read; keeps
 * the surface intentionally small so tests stay coupled only to what
 * the production code calls.
 */
class FakeUser {

  /**
   * Constructs the fake user.
   *
   * @param int|string $id
   *   The id this fake returns from 'id()'.
   */
  public function __construct(protected readonly int|string $id) {
  }

  /**
   * Returns the fake user's id.
   *
   * @return int|string
   *   The id supplied to the constructor.
   */
  public function id(): int|string {
    return $this->id;
  }

}
