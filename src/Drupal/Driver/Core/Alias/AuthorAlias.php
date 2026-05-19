<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Alias;

use Drupal\Driver\Alias\PreCreateAliasInterface;
use Drupal\Driver\Entity\EntityStubInterface;
use Drupal\Driver\Exception\CreationAliasResolutionException;

/**
 * Resolves a username on a node stub into the corresponding 'uid' value.
 *
 * Reads the value at 'author', looks up the user by name, and writes the
 * user's id to 'uid'. The 'author' key is removed from the stub once
 * resolved. Throws when the username does not match any existing user.
 */
class AuthorAlias implements PreCreateAliasInterface {

  /**
   * Lookup callable for resolving a username to a user object.
   *
   * Receives a username and returns the user object (with an 'id()'
   * method) when found, or NULL when not.
   *
   * @var \Closure(string): ?object
   */
  protected \Closure $userLookup;

  /**
   * Constructs the alias.
   *
   * @param \Closure(string): ?object|null $user_lookup
   *   Lookup callable. NULL uses 'user_load_by_name()' from the Drupal API.
   */
  public function __construct(?\Closure $user_lookup = NULL) {
    $this->userLookup = $user_lookup ?? static function (string $name): ?object {
      $user = user_load_by_name($name);

      return $user ?: NULL;
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'author';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'node';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return "Resolves a username supplied via 'author' to the owning user's 'uid'. Throws when the user does not exist.";
  }

  /**
   * {@inheritdoc}
   */
  public function applyToStub(EntityStubInterface $stub): void {
    $name = (string) $stub->getValue('author');

    if ($name === '') {
      throw new CreationAliasResolutionException("Cannot create node because the 'author' creation alias is set but empty.");
    }

    $user = ($this->userLookup)($name);

    if ($user === NULL) {
      throw new CreationAliasResolutionException(sprintf("Cannot create node because user '%s', referenced via the 'author' creation alias, does not exist.", $name));
    }

    if (!method_exists($user, 'id')) {
      throw new CreationAliasResolutionException(sprintf("Cannot create node because the 'author' lookup returned an object without an 'id()' method while resolving '%s'.", $name));
    }

    // Cast to int so the downstream entity-reference handler treats the
    // value as a pre-resolved id and skips its own validation query.
    $stub->setValue('uid', (int) $user->id());
    $stub->removeValue('author');
  }

}
