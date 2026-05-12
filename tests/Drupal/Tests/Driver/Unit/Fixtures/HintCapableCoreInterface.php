<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Fixtures;

use Drupal\Driver\Capability\CreationHintCapabilityInterface;
use Drupal\Driver\Core\CoreInterface;

/**
 * Composite test interface used to exercise the hint-capable Core path.
 *
 * Extends both 'CoreInterface' and 'CreationHintCapabilityInterface' so a
 * single PHPUnit mock can satisfy 'setCore()' and the
 * 'instanceof CreationHintCapabilityInterface' guard in the same instance.
 */
interface HintCapableCoreInterface extends CoreInterface, CreationHintCapabilityInterface {
}
