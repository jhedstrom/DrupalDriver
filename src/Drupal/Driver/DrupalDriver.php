<?php

namespace Drupal\Driver;

use Drupal\Driver\Exception\BootstrapException;
use Drupal\Driver\SubDriverFinderInterface;

use Behat\Behat\Tester\Exception\PendingException;

/**
 * Fully bootstraps Drupal and uses native API calls.
 */
class DrupalDriver implements DriverInterface, SubDriverFinderInterface {

  /**
   * Track whether Drupal has been bootstrapped.
   *
   * @var bool
   */
  private $bootstrapped = FALSE;

  /**
   * Drupal core object.
   *
   * @var \Drupal\Driver\Cores\CoreInterface
   */
  public $core;

  /**
   * System path to the Drupal installation.
   *
   * @var string
   */
  private $drupalRoot;

  /**
   * URI for the Drupal installation.
   *
   * @var string
   */
  private $uri;

  /**
   * Drupal core version.
   *
   * @var integer
   */
  public $version;

  /**
   * Set Drupal root and URI.
   */
  public function __construct($drupalRoot, $uri) {
    $this->drupalRoot = realpath($drupalRoot);
    if (!$this->drupalRoot) {
      throw new BootstrapException(sprintf('No Drupal installation found at %s', $drupalRoot));
    }
    $this->uri = $uri;
    $this->version = $this->getDrupalVersion();
  }

  /**
   * {@inheritDoc}
   */
  public function getRandom() {
    return $this->getCore()->getRandom();
  }

  /**
   * {@inheritDoc}
   */
  public function bootstrap() {
    $this->getCore()->bootstrap();
    $this->bootstrapped = TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function isBootstrapped() {
    // Assume the blackbox is always bootstrapped.
    return $this->bootstrapped;
  }

  /**
   * {@inheritDoc}
   */
  public function userCreate(\stdClass $user) {
    $this->getCore()->userCreate($user);
  }

  /**
   * {@inheritDoc}
   */
  public function userDelete(\stdClass $user) {
    $this->getCore()->userDelete($user);
  }

  public function processBatch() {
    $this->getCore()->processBatch();
  }

  /**
   * {@inheritDoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $this->getCore()->userAddRole($user, $role_name);
  }

  /**
   * {@inheritDoc}
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    throw new PendingException(sprintf('Currently no ability to access watchdog entries in %s', $this));
  }

  /**
   * {@inheritDoc}
   */
  public function clearCache($type = NULL) {
    $this->getCore()->clearCache();
  }

  /**
   * {@inheritDoc}
   */
  public function getSubDriverPaths() {
    // Ensure system is bootstrapped.
    if (!$this->isBootstrapped()) {
      $this->bootstrap();
    }

    // Get enabled modules.
    $modules = $this->getCore()->getModuleList();
    $paths = array();
    foreach ($modules as $module) {
      $paths[] = $this->drupalRoot . DIRECTORY_SEPARATOR . \drupal_get_path('module', $module);
    }

    // Themes.
    // @todo

    // Active profile
    // @todo
    return $paths;
  }

  /**
   * Determine major Drupal version.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *
   * @see drush_drupal_version()
   */
  function getDrupalVersion() {
    if (!isset($this->version)) {
      // Support 6, 7 and 8.
      $version_constant_paths = array(
        // Drupal 6.
        '/modules/system/system.module',
        // Drupal 7.
        '/includes/bootstrap.inc',
        // Drupal 8.
        '/autoload.php',
        '/core/includes/bootstrap.inc',
      );

      if ($this->drupalRoot === FALSE) {
        throw new BootstrapException('`drupal_root` parameter must be defined.');
      }

      foreach ($version_constant_paths as $path) {
        if (file_exists($this->drupalRoot . $path)) {
          require_once $this->drupalRoot . $path;
        }
      }
      if (defined('VERSION')) {
        $version = VERSION;
      }
      elseif (defined('\Drupal::VERSION')) {
        $version = \Drupal::VERSION;
      }
      else {
        throw new BootstrapException('Unable to determine Drupal core version. Supported versions are 6, 7, and 8.');
      }

      // Extract the major version from VERSION.
      $version_parts = explode('.', $version);
      if (is_numeric($version_parts[0])) {
        $this->version = (integer) $version_parts[0];
      }
      else {
        throw new BootstrapException(sprintf('Unable to extract major Drupal core version from version string %s.', $version));
      }
    }
    return $this->version;
  }

  /**
   * Instantiate and set Drupal core class.
   *
   * @param array $availableCores
   *   A major-version-keyed array of available core controllers.
   */
  public function setCore($availableCores) {
    if (!isset($availableCores[$this->version])) {
      throw new BootstrapException(sprintf('There is no available Drupal core controller for Drupal version %s.', $this->version));
    }
    $this->core = $availableCores[$this->version];
  }

  /**
   * Automatically set the core from the current version.
   */
  public function setCoreFromVersion() {
    $core = '\Drupal\Driver\Cores\Drupal' . $this->getDrupalVersion();
    $this->core = new $core($this->drupalRoot, $this->uri);
  }

  /**
   * Return current core.
   */
  public function getCore() {
    return $this->core;
  }

  /**
   * {@inheritDoc}
   */
  public function createNode($node) {
    return $this->getCore()->nodeCreate($node);
  }

  /**
   * {@inheritDoc}
   */
  public function nodeDelete($node) {
    return $this->getCore()->nodeDelete($node);
  }

  /**
   * {@inheritDoc}
   */
  public function runCron() {
    if (!$this->getCore()->runCron()) {
      throw new \Exception('Failed to run cron.');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function createTerm(\stdClass $term) {
    return $this->getCore()->termCreate($term);
  }

  /**
   * {@inheritDoc}
   */
  public function termDelete(\stdClass $term) {
    return $this->getCore()->termDelete($term);
  }

  /**
   * {@inheritDoc}
   */
  public function roleCreate(array $permissions) {
    return $this->getCore()->roleCreate($permissions);
  }

  /**
   * {@inheritDoc}
   */
  public function roleDelete($rid) {
    $this->getCore()->roleDelete($rid);
  }

  /**
   * {@inheritDoc}
   */
  public function isField($entity_type, $field_name) {
    return $this->getCore()->isField($entity_type, $field_name);
  }

}
