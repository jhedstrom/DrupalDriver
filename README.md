<h1 align="center">Drupal Driver</h1>

<div align="center">

[![Latest Stable Version](https://poser.pugx.org/drupal/drupal-driver/v/stable.svg)](https://packagist.org/packages/drupal/drupal-driver)
[![Total Downloads](https://poser.pugx.org/drupal/drupal-driver/downloads.svg)](https://packagist.org/packages/drupal/drupal-driver)
[![License](https://poser.pugx.org/drupal/drupal-driver/license.svg)](https://packagist.org/packages/drupal/drupal-driver)

[![ci](https://github.com/jhedstrom/DrupalDriver/actions/workflows/ci.yml/badge.svg)](https://github.com/jhedstrom/DrupalDriver/actions/workflows/ci.yml)
[![GitHub Issues](https://img.shields.io/github/issues/jhedstrom/DrupalDriver.svg)](https://github.com/jhedstrom/DrupalDriver/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/jhedstrom/DrupalDriver.svg)](https://github.com/jhedstrom/DrupalDriver/pulls)
</div>

A collection of lightweight drivers with a common interface for
interacting with [Drupal](https://www.drupal.org). These are generally
intended for testing and are not meant to be API-complete.

> **Note:** We are actively working on version 3.x which will drop
> Drupal 6/7 support, modernise the codebase, and introduce integration
> testing. See the
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
$driver->createNode($node);
```

## Credits

 * Originally developed by [Jonathan Hedstrom](https://github.com/jhedstrom)
 * Maintainers
   * [Alex Skrypnyk](https://github.com/AlexSkrypnyk)
   * [All contributors](https://github.com/jhedstrom/DrupalDriver/graphs/contributors)

## Additional resources

 * [Drupal Driver documentation](https://drupal-drivers.readthedocs.org)

## Release notes

See [CHANGELOG](CHANGELOG.MD).

## Contributing

Features and bug fixes are welcome!

See [CONTRIBUTING.md](https://github.com/jhedstrom/DrupalDriver/blob/master/CONTRIBUTING.md) for more information.
