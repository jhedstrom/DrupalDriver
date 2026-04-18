<?php

declare(strict_types=1);

namespace Drupal\Driver;

/**
 * Contract for the blackbox driver.
 *
 * Performs no backend operations. Implementations only satisfy the base
 * driver contract; all capability interfaces are deliberately absent.
 */
interface BlackboxDriverInterface extends DriverInterface {

}
