<?php

/**
 * @file
 * Minimal bootstrap for Rector static analysis.
 *
 * Registers Drupal's Tests / KernelTests / TestTools namespaces with the
 * Composer autoloader so Rector can resolve the types of classes that extend
 * KernelTestBase. Skips the PHPUnit-specific initialisation in
 * drupal/core/tests/bootstrap.php, which asserts a live PHPUnit runner and
 * fatals when invoked from any other tool.
 */

declare(strict_types=1);

$loader = require __DIR__ . '/drupal/autoload.php';
$loader->add('Drupal\\BuildTests', __DIR__ . '/drupal/core/tests');
$loader->add('Drupal\\Tests', __DIR__ . '/drupal/core/tests');
$loader->add('Drupal\\TestSite', __DIR__ . '/drupal/core/tests');
$loader->add('Drupal\\KernelTests', __DIR__ . '/drupal/core/tests');
$loader->add('Drupal\\FunctionalTests', __DIR__ . '/drupal/core/tests');
$loader->add('Drupal\\FunctionalJavascriptTests', __DIR__ . '/drupal/core/tests');
$loader->add('Drupal\\TestTools', __DIR__ . '/drupal/core/tests');
