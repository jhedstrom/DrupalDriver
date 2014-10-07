[![Build Status](https://travis-ci.org/jhedstrom/DrupalDriver.svg?branch=master)](https://travis-ci.org/jhedstrom/DrupalDriver)

Provides a collection of light-weight drivers with a common interface for interacting with [Drupal](http://drupal.org). These are generally intended for testing, and are not meant to be API-complete.

[![Latest Stable Version](https://poser.pugx.org/drupal/drupal-driver/v/stable.svg)](https://packagist.org/packages/drupal/drupal-driver) [![Total Downloads](https://poser.pugx.org/drupal/drupal-driver/downloads.svg)](https://packagist.org/packages/drupal/drupal-driver) [![License](https://poser.pugx.org/drupal/drupal-driver/license.svg)](https://packagist.org/packages/drupal/drupal-driver)

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
