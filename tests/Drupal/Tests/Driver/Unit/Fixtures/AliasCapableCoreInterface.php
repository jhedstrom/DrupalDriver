<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Fixtures;

use Drupal\Driver\Capability\CreationAliasCapabilityInterface;
use Drupal\Driver\Core\CoreInterface;

/**
 * Composite test interface used to exercise the alias-capable Core path.
 *
 * Extends both 'CoreInterface' and 'CreationAliasCapabilityInterface' so
 * a single PHPUnit mock can satisfy 'setCore()' and the
 * 'instanceof CreationAliasCapabilityInterface' guard in the same instance.
 */
interface AliasCapableCoreInterface extends CoreInterface, CreationAliasCapabilityInterface {
}
