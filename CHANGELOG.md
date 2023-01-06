# Change log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
## [2.2.2]
### Added
  * [#258](https://github.com/jhedstrom/DrupalDriver/pull/258) Document testing of the Drupal Driver locally for contributors.
### Fixed
  * [#260](https://github.com/jhedstrom/DrupalDriver/pull/260) Fix taxonomy term creation for hierarchies.
## [2.2.1]
  * [#250](https://github.com/jhedstrom/DrupalDriver/pull/250) Drupal 10 compatibility.
## [2.2.0]
### Fixed
  * [#232](https://github.com/jhedstrom/DrupalDriver/pull/232) Fixes typo in ImageHandler.
  * [#233](https://github.com/jhedstrom/DrupalDriver/pull/233) Fixes Drupal 7.79 compatibility.
  * [#244](https://github.com/jhedstrom/DrupalDriver/pull/244) Drupal Coder updates.
### Added
  * [#245](https://github.com/jhedstrom/DrupalDriver/pull/245) PHP 8.1 compatibility.
  * [#247](https://github.com/jhedstrom/DrupalDriver/issues/247) Drupal 10 and PHP 8.1 compatibility.
## [2.1.1]
### Fixed
  * [#233](https://github.com/jhedstrom/DrupalDriver/pull/233) Prevent PHP warning in Drupal 7.79 and above.
  * [#232](https://github.com/jhedstrom/DrupalDriver/pull/232) Fix type in ImageHandler.
## [2.1.0]
### Added
  * [#186](https://github.com/jhedstrom/DrupalDriver/issues/168) Provide a method to directly authenticate on Drupal 8.
### Changed
  * Remove testing on PHP 5.6, added testing on PHP 7.3 and 7.4.
  * [#214](https://github.com/jhedstrom/DrupalDriver/pull/214) Fix D8 deprectations.
  * [#224](https://github.com/jhedstrom/DrupalDriver/pull/224) Support Drupal 9.
### Fixed
  * [#217](https://github.com/jhedstrom/DrupalDriver/pull/217) Fix fatal error in field handler.
  * [#219](https://github.com/jhedstrom/DrupalDriver/issues/219) Fix php notice in exception message.
## [2.0.0] 2019-09-27
## [2.0.0 rc1] 2019-07-25
### Changed
  * [#207](https://github.com/jhedstrom/DrupalDriver/pull/207) Require PHP 5.6 or higher.
## [2.0.0 alpha6] 2018-09-21
### Added
  * [#190](https://github.com/jhedstrom/DrupalDriver/pull/190) Added Drush entity support.
  * [#168](https://github.com/jhedstrom/DrupalDriver/issues/168) Added ListHandlers for Drupal 8.
### Changed
  * [#203](https://github.com/jhedstrom/DrupalDriver/pull/203) Removes testing of HHVM.
## [2.0.0 alpha5] 2018-09-21
### Fixed
  * [#199](https://github.com/jhedstrom/DrupalDriver/pull/199): Fixes type
    introduced in #198.
## [2.0.0 alpha4] 2018-09-21
### Added
  * [#191](https://github.com/jhedstrom/DrupalDriver/pull/191): Adds field
    handler for address fields.
  * [#196](https://github.com/jhedstrom/DrupalDriver/pull/196): Add a
    storeOriginalConfiguration method.
  * [#197](https://github.com/jhedstrom/DrupalDriver/pull/197): Added a method
    configGetOriginal which will return the original config data.
### Fixed
  * [#193](https://github.com/jhedstrom/DrupalDriver/pull/193): Fixing the
    ListTextHandler to allow a key to also be 0
  * [#198](https://github.com/jhedstrom/DrupalDriver/pull/198): Use
    cache:rebuild instead of cache-clear all with Drush 9.
## [2.0.0 alpha3] 2018-06-21
### Added
  * [#89](https://github.com/jhedstrom/DrupalDriver/pull/89): Adds Embridge asset
    item field handler.
  * [#184](https://github.com/jhedstrom/DrupalDriver/pull/184): Extract and store
    uid in DrushDriver::userCreate()
  * [#185](https://github.com/jhedstrom/DrupalDriver/pull/185): Support for
    deleting any entity, not just content entities.
## [2.0.0 alpha2] 2018-03-21
### Added
  * [#126](https://github.com/jhedstrom/DrupalDriver/pull/126): Infer timezone
    in DateTime field handler.
  * [#180](https://github.com/jhedstrom/DrupalDriver/pull/180): Support email
    collection when the Mail System module is enabled.
### Fixed
  * [#182](https://github.com/jhedstrom/DrupalDriver/pull/182): Persist mail
    collection to config storage so email collection works across bootstraps.

## [2.0.0 alpha1] 2018-03-19
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


[Unreleased]: https://github.com/jhedstrom/DrupalDriver/compare/v2.2.2...HEAD
[2.2.2]: https://github.com/jhedstrom/DrupalDriver/compare/v2.2.1...v2.2.2
[2.2.1]: https://github.com/jhedstrom/DrupalDriver/compare/v2.2.0...v2.2.1
[2.2.0]: https://github.com/jhedstrom/DrupalDriver/compare/v2.1.1...v2.2.0
[2.1.1]: https://github.com/jhedstrom/DrupalDriver/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/jhedstrom/DrupalDriver/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/jhedstrom/DrupalDriver/compare/v2.0.0-rc1...v2.0.0
[2.0.0 rc1]: https://github.com/jhedstrom/DrupalDriver/compare/v2.0.0-alpha6...v2.0.0-rc1
[2.0.0 alpha6]: https://github.com/jhedstrom/DrupalDriver/compare/v2.0.0-alpha5...HEAD
[2.0.0 alpha5]: https://github.com/jhedstrom/DrupalDriver/compare/v2.0.0-alpha4...v2.0.0-alpha5
[2.0.0 alpha4]: https://github.com/jhedstrom/DrupalDriver/compare/v2.0.0-alpha3...v2.0.0-alpha4
[2.0.0 alpha3]: https://github.com/jhedstrom/DrupalDriver/compare/v2.0.0-alpha2...v2.0.0-alpha3
[2.0.0 alpha2]: https://github.com/jhedstrom/DrupalDriver/compare/v2.0.0-alpha1...v2.0.0-alpha2
[2.0.0 alpha1]: https://github.com/jhedstrom/DrupalDriver/compare/v1.4.0...v2.0.0-alpha1
[1.4.0]: https://github.com/jhedstrom/DrupalDriver/compare/v1.3.2...v1.4.0
