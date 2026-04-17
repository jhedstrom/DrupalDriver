# Contributing

Features and bug fixes are welcome! First-time contributors can
jump in with the issues tagged [good first issue](https://github.com/jhedstrom/DrupalDriver/issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22).

> **Note:** We are actively working on version 3.x which will
> overhaul the test infrastructure, drop Drupal 6/7 support, and
> introduce SQLite-based kernel tests. See the
> [3.x epic](https://github.com/jhedstrom/DrupalDriver/issues/312)
> for details.

## How this project works

Drupal Driver is a PHP library that provides a common interface
for interacting with Drupal programmatically. It abstracts
version-specific details so that consumers (such as
[Drupal Extension](https://github.com/jhedstrom/drupalextension))
can create and manipulate entities, manage users and roles, run
cron, and clear caches without knowing which Drupal version is
running.

### Drivers

The library provides three drivers, each offering a different
level of access to Drupal:

- **Blackbox** - No Drupal bootstrap. Limited to operations
  that do not require access to Drupal internals.

- **Drupal API** - Bootstraps Drupal directly in PHP. Delegates
  to a version-specific Core class (`Drupal8.php` covers
  Drupal 8 through 11) for entity CRUD, field handling, user
  and role management, cron, caching, and mail.

- **Drush** - Uses the Drush command line tool to interact
  with Drupal over a subprocess.

### Field handlers

Field handlers translate human-readable values into the format
expected by Drupal's field storage system. Each handler implements
`FieldHandlerInterface::expand($values)` and is resolved by
convention from the Drupal field type name (e.g., `entity_reference`
resolves to `EntityReferenceHandler`).

Handlers that perform real transformation include:

| Handler | What it does |
| --- | --- |
| `EntityReferenceHandler` | Looks up entities by name and returns target IDs |
| `DatetimeHandler` | Converts dates with timezone handling |
| `DaterangeHandler` | Converts date ranges with timezone handling |
| `TimeHandler` | Converts time strings to seconds past midnight |
| `ListStringHandler` and siblings | Resolves human labels to stored keys |
| `ImageHandler` | Creates file entities from file paths |
| `FileHandler` | Creates file entities from file paths |

### What are we testing?

This repository tests the drivers and field handlers. The current
test suite includes:

- **PHPUnit tests** for field handlers that can be tested without
  a Drupal bootstrap (TimeHandler, LinkHandler, NameHandler) and
  for driver logic (DrushDriver version detection, user ID
  parsing).

- **PhpSpec specs** (legacy) for core driver and exception classes.

The test suite does **not** currently test handlers against a real
Drupal installation. Integration testing with SQLite-based kernel
tests is planned for v3.x.

## Setting up the local environment

Testing is performed automatically in GitHub Actions when a PR is
submitted. To execute tests locally, you can use either Docker or
a local PHP installation.

### Local PHP (recommended)

If you have PHP 8.2+ installed locally:

```shell
composer install
composer test
```

Run a specific test:

```shell
XDEBUG_MODE=off vendor/bin/phpunit --filter TimeHandlerTest
```

### Docker

To test with a specific PHP version using Docker:

```shell
export PHP_VERSION=8.3
export DRUPAL_VERSION=11
export DOCKER_USER_ID=${UID}
```

```shell
docker compose up -d
docker compose exec -T php composer install
docker compose exec -T php composer test
```

### Commands

| Command | Description |
| --- | --- |
| `composer lint` | PHP syntax check, PHPCS coding standards, Rector dry-run |
| `composer lint-fix` | Auto-fix: Rector + PHPCBF |
| `composer test` | PHPUnit + PhpSpec |

## Before submitting a change

- Run `composer lint` and `composer test` locally to verify
  all checks pass.
- Check that changes from `composer require` are not included
  in your submitted PR.
- Before testing another PHP or Drupal version with Docker,
  remove `composer.lock` and `vendor/`.
