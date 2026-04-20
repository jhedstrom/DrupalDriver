<h1 align="center">Drupal Driver</h1>

<div align="center">

[![Latest Stable Version](https://poser.pugx.org/drupal/drupal-driver/v/stable.svg)](https://packagist.org/packages/drupal/drupal-driver)
[![Total Downloads](https://poser.pugx.org/drupal/drupal-driver/downloads.svg)](https://packagist.org/packages/drupal/drupal-driver)
[![License](https://poser.pugx.org/drupal/drupal-driver/license.svg)](https://packagist.org/packages/drupal/drupal-driver)

[![ci](https://github.com/jhedstrom/DrupalDriver/actions/workflows/ci.yml/badge.svg)](https://github.com/jhedstrom/DrupalDriver/actions/workflows/ci.yml)
[![GitHub Issues](https://img.shields.io/github/issues/jhedstrom/DrupalDriver.svg)](https://github.com/jhedstrom/DrupalDriver/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/jhedstrom/DrupalDriver.svg)](https://github.com/jhedstrom/DrupalDriver/pulls)

[![Join our community](https://img.shields.io/badge/Join%20our%20community-Slack-4A154B?style=for-the-badge&logo=slack&logoColor=white)](https://drupal.slack.com/archives/C4T2JHG9K)
</div>

A collection of lightweight drivers with a common interface for
interacting with [Drupal](https://www.drupal.org). These are generally
intended for testing and are not meant to be API-complete.

> **Note:** This `master` branch is under heavy development for
> version 3.x. Drupal 6 and 7 support has been dropped. For the
> 2.x maintenance line, use the
> [`2.x` branch](https://github.com/jhedstrom/DrupalDriver/tree/2.x).
> See the
> [3.x epic](https://github.com/jhedstrom/DrupalDriver/issues/312)
> for details and progress.

## Drivers

| Driver | Description |
| --- | --- |
| **Blackbox** | No Drupal bootstrap. Interacts only through HTTP. |
| **Drupal API** | Bootstraps Drupal directly in PHP for programmatic access to entities, fields, users, and configuration. |
| **Drush** | Interacts with Drupal through the [Drush](https://www.drush.org) command line tool. |

## Installation

```shell
composer require drupal/drupal-driver
```

## Usage

```php
use Drupal\Driver\DrupalDriver;

require 'vendor/autoload.php';

$path = './web';           // Path to Drupal root.
$uri  = 'http://my-site';  // Site URI.

$driver = new DrupalDriver($path, $uri);
$driver->setCoreFromVersion();

// Bootstrap Drupal.
$driver->bootstrap();

// Create a node.
$node = (object) [
  'type' => 'article',
  'uid' => 1,
  'title' => $driver->getRandom()->name(),
];
$driver->nodeCreate($node);
```

## Extending

The driver lets consumer projects override two things without forking:
individual field handlers and the top-level Core implementation.

### Custom field handler

Implement `Drupal\Driver\Core\Field\FieldHandlerInterface` (extending
`AbstractHandler` is the usual path):

```php
namespace MyProject\Driver\Field;

use Drupal\Driver\Core\Field\AbstractHandler;

class PhoneHandler extends AbstractHandler {
  public function expand(mixed $values): array {
    // Convert each scenario-facing phone string into the storage shape
    // Drupal's field system expects (a list of deltas keyed by column).
    return array_map(static fn (string $number): array => ['value' => $number], (array) $values);
  }
}
```

Register it on the active Core instance, typically in a test bootstrap:

```php
$driver = new DrupalDriver($root, $uri);
$driver->setCoreFromVersion();
$driver->getCore()->registerFieldHandler('phone', \MyProject\Driver\Field\PhoneHandler::class);
```

Consumer registrations win over the handlers this project ships for the
same field type. Registration order at runtime is: driver default
handlers (populated by `Core::registerDefaultFieldHandlers()` at
construction) → consumer registrations → registry lookup at `expand()`
time, with a fall-through to `DefaultHandler` for field types no handler
claims.

### Custom Core

Implement `Drupal\Driver\Core\CoreInterface`. The class name and
namespace do not matter. The easiest path is to extend
`Drupal\Driver\Core\Core` and add version-specific field handlers by
re-scanning your own `Field/` directory:

```php
namespace MyProject\Driver;

use Drupal\Driver\Core\Core as BaseCore;

class Core extends BaseCore {
  protected function registerDefaultFieldHandlers(): void {
    parent::registerDefaultFieldHandlers();
    $this->registerHandlersFromDirectory(__DIR__ . '/Field', __NAMESPACE__ . '\\Field');
  }
}
```

Inject it with `$driver->setCore($core)`:

```php
$driver = new DrupalDriver($root, $uri);
$driver->setCore(new \MyProject\Driver\Core($root, $uri));
```

## Credits

 * Originally developed by [Jonathan Hedstrom](https://github.com/jhedstrom)
 * Maintainers
   * [Alex Skrypnyk](https://github.com/AlexSkrypnyk)
   * [All contributors](https://github.com/jhedstrom/DrupalDriver/graphs/contributors)

## Release notes

See [GitHub Releases](https://github.com/jhedstrom/DrupalDriver/releases).

## Contributing

Features and bug fixes are welcome!

See [CONTRIBUTING.md](https://github.com/jhedstrom/DrupalDriver/blob/master/CONTRIBUTING.md) for more information.
