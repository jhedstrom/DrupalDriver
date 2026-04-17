<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;
use Drupal\Driver\Core\Core;
use Drupal\Driver\Core\CoreInterface;
use Drupal\Driver\Core\CoreAuthenticationInterface;
use Drupal\Driver\Exception\BootstrapException;

/**
 * Fully bootstraps Drupal and uses native API calls.
 */
class DrupalDriver implements DriverInterface, SubDriverFinderInterface, AuthenticationDriverInterface {

  /**
   * Track whether Drupal has been bootstrapped.
   */
  private bool $bootstrapped = FALSE;

  /**
   * Drupal core object.
   */
  public CoreInterface $core;

  /**
   * System path to the Drupal installation.
   */
  private readonly string $drupalRoot;

  /**
   * URI for the Drupal installation.
   */
  private readonly string $uri;

  /**
   * Drupal core version.
   */
  public int $version;

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
  public function __construct(string $drupal_root, string $uri) {
    $this->drupalRoot = realpath($drupal_root);
    $this->uri = $uri;
    if ($this->drupalRoot === '' || $this->drupalRoot === '0') {
      throw new BootstrapException(sprintf('No Drupal installation found at %s', $drupal_root));
    }
    $this->version = $this->getDrupalVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom(): Random {
    return $this->getCore()->getRandom();
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap(): void {
    $this->getCore()->bootstrap();
    $this->bootstrapped = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped(): bool {
    // Assume the blackbox is always bootstrapped.
    return $this->bootstrapped;
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user): void {
    $this->getCore()->userCreate($user);
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user): void {
    $this->getCore()->userDelete($user);
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch(): void {
    $this->getCore()->processBatch();
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role_name): void {
    $this->getCore()->userAddRole($user, $role_name);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL): never {
    throw new \RuntimeException(sprintf('Currently no ability to access watchdog entries in %s', $this));
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache($type = NULL): void {
    $this->getCore()->clearCache();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubDriverPaths(): array {
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
  public function getDrupalVersion(): int {
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
   *   The actual major version number (10, 11, 12, etc.).
   */
  protected function detectMajorVersion(): int {
    $version_files = [
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

    if ((int) $major < 10) {
      throw new BootstrapException(sprintf('Unsupported Drupal core version %s. Drupal 10 or higher is required.', $version_string));
    }

    return (int) $major;
  }

  /**
   * Reads the Drupal VERSION constant.
   */
  protected function readVersionConstant(): string {
    if (defined(\Drupal::class . '::VERSION')) {
      return \Drupal::VERSION;
    }

    throw new BootstrapException('Unable to determine Drupal core version. Supported versions are 10 and 11.');
  }

  /**
   * Instantiate and set Drupal core class.
   *
   * @param array<int, \Drupal\Driver\Core\CoreInterface> $available_cores
   *   A major-version-keyed array of available core controllers.
   */
  public function setCore(array $available_cores): void {
    if (!isset($available_cores[$this->version])) {
      throw new BootstrapException(sprintf('There is no available Drupal core controller for Drupal version %s.', $this->version));
    }
    $this->core = $available_cores[$this->version];
  }

  /**
   * Automatically set the core from the current version.
   *
   * Walks from the detected Drupal version down to the default Core class,
   * using the first class that exists in the lookup chain:
   * Drupal\Driver\Core{N}\Core → ... → Drupal\Driver\Core\Core.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when no Core implementation is found for the detected version.
   */
  public function setCoreFromVersion(): void {
    $version = $this->getDrupalVersion();
    $candidates = [];
    for ($n = $version; $n >= 10; $n--) {
      $candidates[] = sprintf('Drupal\\Driver\\Core%d\\Core', $n);
    }
    $candidates[] = Core::class;
    foreach ($candidates as $class) {
      if (class_exists($class)) {
        $this->core = new $class($this->drupalRoot, $this->uri);
        return;
      }
    }
    throw new BootstrapException(sprintf('No Core implementation found for Drupal version %s.', $version));
  }

  /**
   * Return current core.
   */
  public function getCore(): CoreInterface {
    return $this->core;
  }

  /**
   * {@inheritdoc}
   */
  public function createNode($node): object {
    return $this->getCore()->nodeCreate($node);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node): void {
    $this->getCore()->nodeDelete($node);
  }

  /**
   * {@inheritdoc}
   */
  public function runCron(): bool {
    return $this->getCore()->runCron();
  }

  /**
   * {@inheritdoc}
   */
  public function createTerm(\stdClass $term): object {
    return $this->getCore()->termCreate($term);
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term): bool {
    return $this->getCore()->termDelete($term);
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions): string {
    return $this->getCore()->roleCreate($permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete($rid): void {
    $this->getCore()->roleDelete($rid);
  }

  /**
   * {@inheritdoc}
   */
  public function isField($entity_type, $field_name): bool {
    return $this->getCore()->isField($entity_type, $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField($entity_type, $field_name): bool {
    return $this->getCore()->isBaseField($entity_type, $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function languageCreate(\stdClass $language): \stdClass|false {
    return $this->getCore()->languageCreate($language);
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete(\stdClass $language): void {
    $this->getCore()->languageDelete($language);
  }

  /**
   * {@inheritdoc}
   */
  public function configGet($name, $key): mixed {
    return $this->getCore()->configGet($name, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet($name, $key, $value): void {
    $this->getCore()->configSet($name, $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches(): void {
    $this->getCore()->clearStaticCaches();
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity($entity_type, \stdClass $entity): object {
    return $this->getCore()->entityCreate($entity_type, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete($entity_type, \stdClass $entity): void {
    $this->getCore()->entityDelete($entity_type, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function startCollectingMail(): void {
    $this->getCore()->startCollectingMail();
  }

  /**
   * {@inheritdoc}
   */
  public function stopCollectingMail(): void {
    $this->getCore()->stopCollectingMail();
  }

  /**
   * {@inheritdoc}
   */
  public function getMail(): array {
    return $this->getCore()->getMail();
  }

  /**
   * {@inheritdoc}
   */
  public function clearMail(): void {
    $this->getCore()->clearMail();
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail($body, $subject, $to, $langcode): bool {
    return $this->getCore()->sendMail($body, $subject, $to, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleInstall($module_name): void {
    $this->getCore()->moduleInstall($module_name);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleUninstall($module_name): void {
    $this->getCore()->moduleUninstall($module_name);
  }

  /**
   * {@inheritdoc}
   */
  public function login(\stdClass $user): void {
    $auth = $this->getAuthCore();

    if ($auth instanceof CoreAuthenticationInterface) {
      $auth->login($user);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logout(): void {
    $auth = $this->getAuthCore();

    if ($auth instanceof CoreAuthenticationInterface) {
      $auth->logout();
    }
  }

  /**
   * Returns the core as an authentication driver, or NULL if unsupported.
   *
   * @return \Drupal\Driver\Core\CoreAuthenticationInterface|null
   *   The authentication-capable core, or NULL.
   */
  protected function getAuthCore(): ?CoreAuthenticationInterface {
    $core = $this->getCore();

    return $core instanceof CoreAuthenticationInterface ? $core : NULL;
  }

}
