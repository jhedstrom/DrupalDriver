# Change log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
  * [#113](https://github.com/jhedstrom/DrupalDriver/pull/113): Drupal 7 entity
    create/delete support.
  * [#114](https://github.com/jhedstrom/DrupalDriver/pull/114): Base field
    expansion.
  * [#134](https://github.com/jhedstrom/DrupalDriver/pull/134): Support for
    email testing.
### Changed
  * [#173](https://github.com/jhedstrom/DrupalDriver/pull/173): HHVM failures
    allowed, and newer versions of PHPSpec supported.
### Fixed
  * [#170](https://github.com/jhedstrom/DrupalDriver/pull/170): Missing methods
    added to `DriverInterface`.

## [1.4.0] 2018-02-09
### Added
  * [#136](https://github.com/jhedstrom/DrupalDriver/pull/136): Allows relative
    date formats.
### Changed
  * [#159](https://github.com/jhedstrom/DrupalDriver/pull/159): Ignore access on
    Drupal 8 entity reference handler.
  * [#162](https://github.com/jhedstrom/DrupalDriver/pull/162): Remove duplicate
    copy of core's `Random` class.
  * [#163](https://github.com/jhedstrom/DrupalDriver/pull/163): Remove PHP 5.4
    support and test on PHP 7.1 and 7.2.
### Fixed
  * [#117](https://github.com/jhedstrom/DrupalDriver/pull/117): Fix user entity
    reference fields in Drupal 8.
  * [#149](https://github.com/jhedstrom/DrupalDriver/pull/149): Fix condition to
    get target bundle key for entity reference handler.
  * [#151](https://github.com/jhedstrom/DrupalDriver/pull/151): Illegal string
    offset warnings.
  * [#153](https://github.com/jhedstrom/DrupalDriver/pull/153): Fix incorrect
    docblock for `CoreInterface::roleCreate`.


[Unreleased]: https://github.com/jhedstrom/DrupalDriver/compare/v1.4.0...HEAD
[1.4.0]: https://github.com/jhedstrom/DrupalDriver/compare/v1.3.2...v1.4.0
