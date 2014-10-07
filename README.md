[![Build Status](https://travis-ci.org/jhedstrom/DrupalDriver.svg?branch=master)](https://travis-ci.org/jhedstrom/DrupalDriver)

Provides a collection of light-weight drivers with a common interface for interacting with [Drupal](http://drupal.org). These are generally intended for testing, and are not meant to be API-complete.

### Drivers

These drivers support Drupal versions 7 and 8.

* Blackbox
* Direct Drupal API bootstrap
* Drush

### Installation

``` json
{
  "require": {
    "drupal/drupal-driver": "~1.0.*@stable"
  }
}
```

``` bash
$> curl -sS http://getcomposer.org/installer | php
$> php composer.phar install
```
