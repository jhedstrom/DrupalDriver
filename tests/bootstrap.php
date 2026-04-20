<?php

/**
 * @file
 * PHPUnit bootstrap for DrupalDriver unit and kernel tests.
 *
 * Replicates the namespace-loader setup from
 * 'drupal/core/tests/bootstrap.php' without the behat/mink class-alias shim,
 * which Drupal's browser-test infrastructure needs but this project does not.
 * This lets the project manage its own phpunit dependency (for security
 * patching) without pulling in the full 'drupal/core-dev' metapackage and its
 * browser-test transitive tree.
 */

declare(strict_types=1);

$loader = require __DIR__ . '/../vendor/autoload.php';

$drupal_core_tests = __DIR__ . '/../drupal/core/tests';

// Register Drupal's test-framework namespaces (classes live under the
// drupal/core/tests/ tree rather than inside drupal/core/src/).
$loader->add('Drupal\\BuildTests', $drupal_core_tests);
$loader->add('Drupal\\Tests', $drupal_core_tests);
$loader->add('Drupal\\TestSite', $drupal_core_tests);
$loader->add('Drupal\\KernelTests', $drupal_core_tests);
$loader->add('Drupal\\FunctionalTests', $drupal_core_tests);
$loader->add('Drupal\\FunctionalJavascriptTests', $drupal_core_tests);
$loader->add('Drupal\\TestTools', $drupal_core_tests);

// Register test-class namespaces for each core/contrib extension. Mirrors
// the extension-scanning loop in drupal/core/tests/bootstrap.php so kernel
// tests can resolve service/entity classes shipped by modules like commerce.
$drupal_root = realpath(__DIR__ . '/../drupal');

if (is_string($drupal_root) && $drupal_root !== '') {
  $extension_roots = array_filter([
    $drupal_root . '/core/modules',
    $drupal_root . '/core/profiles',
    $drupal_root . '/core/themes',
    $drupal_root . '/modules',
    $drupal_root . '/profiles',
    $drupal_root . '/themes',
  ], is_dir(...));

  foreach ($extension_roots as $root) {
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS | \FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
      if (!str_contains((string) $file->getPathname(), '.info.yml')) {
        continue;
      }

      $extension = substr((string) $file->getFilename(), 0, -9);
      $dir = $file->getPathInfo()->getRealPath();

      if (is_dir($dir . '/src')) {
        $loader->addPsr4('Drupal\\' . $extension . '\\', $dir . '/src');
      }

      if (is_dir($dir . '/tests/src')) {
        $loader->addPsr4('Drupal\\Tests\\' . $extension . '\\', $dir . '/tests/src');
      }
    }
  }
}

// Match the locale, encoding, and timezone defaults Drupal's own bootstrap
// sets. Australia/Sydney is deliberate: UTC+10 with DST exercises edge cases
// that catch timezone regressions.
setlocale(LC_ALL, 'C.UTF-8', 'C');
mb_internal_encoding('utf-8');
mb_language('uni');
date_default_timezone_set('Australia/Sydney');
