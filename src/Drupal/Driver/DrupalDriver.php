<?php

namespace Drupal\Driver;

use Drupal\Driver\Cores\CoreAuthenticationInterface;
use Drupal\Driver\Exception\BootstrapException;

use Behat\Behat\Tester\Exception\PendingException;

/**
 * Fully bootstraps Drupal and uses native API calls.
 */
class DrupalDriver implements DriverInterface, SubDriverFinderInterface, AuthenticationDriverInterface {

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
   * @var int
   */
  public $version;

  /**
   * Set Drupal root and URI.
   *
   * @param string $drupal_root
   *   The Drupal root path.
   * @param string $uri
   *   The URI for the Drupal installation.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when the Drupal installation is not found in the given root path.
   */
  public function __construct($drupal_root, $uri) {
    $this->drupalRoot = realpath($drupal_root);
    if (!$this->drupalRoot) {
      throw new BootstrapException(sprintf('No Drupal installation found at %s', $drupal_root));
    }
    $this->uri = $uri;
    $this->version = $this->getDrupalVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom() {
    return $this->getCore()->getRandom();
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap() {
    $this->getCore()->bootstrap();
    $this->bootstrapped = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped() {
    // Assume the blackbox is always bootstrapped.
    return $this->bootstrapped;
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user) {
    $this->getCore()->userCreate($user);
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user) {
    $this->getCore()->userDelete($user);
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
    $this->getCore()->processBatch();
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $this->getCore()->userAddRole($user, $role_name);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    throw new PendingException(sprintf('Currently no ability to access watchdog entries in %s', $this));
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache($type = NULL) {
    $this->getCore()->clearCache();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubDriverPaths() {
    // Ensure system is bootstrapped.
    if (!$this->isBootstrapped()) {
      $this->bootstrap();
    }

    return $this->getCore()->getExtensionPathList();
  }

  /**
   * Determine major Drupal version.
   *
   * @return int
   *   The major Drupal version.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when the Drupal version could not be determined.
   *
   * @see drush_drupal_version()
   */
  public function getDrupalVersion() {
    if ($this->version !== NULL) {
      return $this->version;
    }

    $this->version = $this->detectMajorVersion();

    return $this->version;
  }

  /**
   * Detects the major Drupal version from the filesystem.
   *
   * @return int
   *   The major version number. Drupal 8+ all return 8 because they share
   *   the same driver (Drupal8.php).
   */
  protected function detectMajorVersion() {
    $version_files = [
      '/modules/system/system.module',
      '/includes/bootstrap.inc',
      '/autoload.php',
      '/core/includes/bootstrap.inc',
    ];

    foreach ($version_files as $path) {
      if (file_exists($this->drupalRoot . $path)) {
        require_once $this->drupalRoot . $path;
      }
    }

    $version_string = $this->readVersionConstant();
    $major = explode('.', $version_string)[0];

    if (!is_numeric($major)) {
      throw new BootstrapException(sprintf('Unable to extract major Drupal core version from version string %s.', $version_string));
    }

    // Drupal 8, 9, 10, 11 all use the same core driver class.
    return (int) $major < 8 ? (int) $major : 8;
  }

  /**
   * Reads the Drupal VERSION constant.
   */
  protected function readVersionConstant() {
    if (defined('VERSION')) {
      return VERSION;
    }

    if (defined(\Drupal::class . '::VERSION')) {
      return \Drupal::VERSION;
    }

    throw new BootstrapException('Unable to determine Drupal core version. Supported versions are 6, 7, 8, 10, and 11.');
  }

  /**
   * Instantiate and set Drupal core class.
   *
   * @param array $available_cores
   *   A major-version-keyed array of available core controllers.
   */
  public function setCore(array $available_cores) {
    if (!isset($available_cores[$this->version])) {
      throw new BootstrapException(sprintf('There is no available Drupal core controller for Drupal version %s.', $this->version));
    }
    $this->core = $available_cores[$this->version];
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
   * {@inheritdoc}
   */
  public function createNode($node) {
    return $this->getCore()->nodeCreate($node);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node) {
    return $this->getCore()->nodeDelete($node);
  }

  /**
   * {@inheritdoc}
   */
  public function runCron() {
    if (!$this->getCore()->runCron()) {
      throw new \Exception('Failed to run cron.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createTerm(\stdClass $term) {
    return $this->getCore()->termCreate($term);
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    return $this->getCore()->termDelete($term);
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions) {
    return $this->getCore()->roleCreate($permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete($rid) {
    $this->getCore()->roleDelete($rid);
  }

  /**
   * {@inheritdoc}
   */
  public function isField($entity_type, $field_name) {
    return $this->getCore()->isField($entity_type, $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField($entity_type, $field_name) {
    return $this->getCore()->isBaseField($entity_type, $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function languageCreate($language) {
    return $this->getCore()->languageCreate($language);
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete($language) {
    $this->getCore()->languageDelete($language);
  }

  /**
   * {@inheritdoc}
   */
  public function configGet($name, $key) {
    return $this->getCore()->configGet($name, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet($name, $key, $value) {
    $this->getCore()->configSet($name, $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
    $this->getCore()->clearStaticCaches();
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity($entity_type, \stdClass $entity) {
    return $this->getCore()->entityCreate($entity_type, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete($entity_type, \stdClass $entity) {
    return $this->getCore()->entityDelete($entity_type, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function startCollectingMail() {
    return $this->getCore()->startCollectingMail();
  }

  /**
   * {@inheritdoc}
   */
  public function stopCollectingMail() {
    return $this->getCore()->stopCollectingMail();
  }

  /**
   * {@inheritdoc}
   */
  public function getMail() {
    return $this->getCore()->getMail();
  }

  /**
   * {@inheritdoc}
   */
  public function clearMail() {
    return $this->getCore()->clearMail();
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail($body, $subject, $to, $langcode) {
    return $this->getCore()->sendMail($body, $subject, $to, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleInstall($module_name) {
    $this->getCore()->moduleInstall($module_name);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleUninstall($module_name) {
    $this->getCore()->moduleUninstall($module_name);
  }

  /**
   * {@inheritdoc}
   */
  public function login(\stdClass $user) {
    $auth = $this->getAuthCore();

    if ($auth) {
      $auth->login($user);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logout() {
    $auth = $this->getAuthCore();

    if ($auth) {
      $auth->logout();
    }
  }

  /**
   * Returns the core as an authentication driver, or NULL if unsupported.
   *
   * @return \Drupal\Driver\Cores\CoreAuthenticationInterface|null
   *   The authentication-capable core, or NULL.
   */
  protected function getAuthCore() {
    $core = $this->getCore();

    return $core instanceof CoreAuthenticationInterface ? $core : NULL;
  }

}
