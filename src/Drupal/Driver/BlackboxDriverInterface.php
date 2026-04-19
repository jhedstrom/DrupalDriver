<?php

declare(strict_types=1);

namespace Drupal\Driver;

/**
 * Contract for the blackbox driver.
 *
 * Performs no backend operations. Implementations satisfy only the base
 * driver contract and MUST NOT additionally implement any interface in the
 * 'Drupal\Driver\Capability' namespace - that way 'instanceof
 * BlackboxDriverInterface' can be relied on as a negative-capability
 * guarantee.
 */
interface BlackboxDriverInterface extends DriverInterface {

}
