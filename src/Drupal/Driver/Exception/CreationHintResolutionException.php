<?php

declare(strict_types=1);

namespace Drupal\Driver\Exception;

/**
 * Thrown when a creation hint cannot resolve its value.
 *
 * Raised by hint implementations when a stub property cannot be
 * translated into a Drupal storage operation - for example, an 'author'
 * alias referencing a username with no matching user account, or a
 * 'parent' term name that does not exist in the target vocabulary.
 *
 * Extends '\InvalidArgumentException' because the failure mode is a
 * caller-supplied value that cannot be made valid by retrying.
 */
class CreationHintResolutionException extends \InvalidArgumentException {
}
