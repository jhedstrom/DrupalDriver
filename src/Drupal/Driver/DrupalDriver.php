<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;
use Drupal\Driver\Capability\AuthenticationCapabilityInterface;
use Drupal\Driver\Core\Core;
use Drupal\Driver\Core\CoreInterface;
use Drupal\Driver\Exception\BootstrapException;

/**
 * Fully bootstraps Drupal and uses native API calls.
 */
class DrupalDriver implements DrupalDriverInterface {

  /**
   * Track whether Drupal has been bootstrapped.
   */
  protected bool $bootstrapped = FALSE;

  /**
   * Drupal core object.
   */
  public CoreInterface $core;

  /**
   * System path to the Drupal installation.
   */
  protected readonly string $drupalRoot;

  /**
   * URI for the Drupal installation.
   */
  protected readonly string $uri;

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
    $resolved = realpath($drupal_root);

    if ($resolved === FALSE) {
      throw new BootstrapException(sprintf('No Drupal installation found at %s', $drupal_root));
    }

    $this->drupalRoot = $resolved;
    $this->uri = $uri;
    $this->version = $this->detectMajorVersion();
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
    return $this->bootstrapped;
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch(): void {
    $this->getCore()->processBatch();
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
    return $this->version;
  }

  /**
   * Injects the active Core implementation.
   *
   * Consumers override the driver's default Core lookup by passing any
   * class that implements 'CoreInterface' - the class name and namespace
   * do not matter. Typically called in a test bootstrap when the project
   * ships its own Core subclass (e.g. one that registers additional field
   * handlers in its 'registerDefaultFieldHandlers()' override).
   *
   * @param \Drupal\Driver\Core\CoreInterface $core
   *   The Core instance the driver should delegate to.
   */
  public function setCore(CoreInterface $core): void {
    $this->core = $core;
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

    foreach ($candidates as $class) {
      if (!class_exists($class)) {
        continue;
      }

      $this->core = new $class($this->drupalRoot, $this->uri);

      return;
    }

    $this->core = new Core($this->drupalRoot, $this->uri);
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
  public function getSubDriverPaths(): array {
    // Ensure system is bootstrapped.
    if (!$this->isBootstrapped()) {
      $this->bootstrap();
    }

    return $this->getCore()->getExtensionPathList();
  }

  /**
   * {@inheritdoc}
   */
  public function login(\stdClass $user): void {
    $auth = $this->getAuthCore();

    if ($auth instanceof AuthenticationCapabilityInterface) {
      $auth->login($user);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logout(): void {
    $auth = $this->getAuthCore();

    if ($auth instanceof AuthenticationCapabilityInterface) {
      $auth->logout();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear(?string $type = NULL): void {
    $this->getCore()->cacheClear($type);
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClearStatic(): void {
    $this->getCore()->cacheClearStatic();
  }

  /**
   * {@inheritdoc}
   */
  public function configGet(string $name, string $key = ''): mixed {
    return $this->getCore()->configGet($name, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function configGetOriginal(string $name, string $key = ''): mixed {
    return $this->getCore()->configGetOriginal($name, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet(string $name, string $key, mixed $value): void {
    $this->getCore()->configSet($name, $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCreate(\stdClass $node): object {
    return $this->getCore()->nodeCreate($node);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete(object $node): void {
    $this->getCore()->nodeDelete($node);
  }

  /**
   * {@inheritdoc}
   */
  public function termCreate(\stdClass $term): object {
    return $this->getCore()->termCreate($term);
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(object $term): bool {
    return $this->getCore()->termDelete($term);
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreate(string $entity_type, \stdClass $entity): object {
    return $this->getCore()->entityCreate($entity_type, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete(string $entity_type, object $entity): void {
    $this->getCore()->entityDelete($entity_type, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function cronRun(): bool {
    return $this->getCore()->cronRun();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldExists(string $entity_type, string $field_name): bool {
    return $this->getCore()->fieldExists($entity_type, $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBase(string $entity_type, string $field_name): bool {
    return $this->getCore()->fieldIsBase($entity_type, $field_name);
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
  public function mailStartCollecting(): void {
    $this->getCore()->mailStartCollecting();
  }

  /**
   * {@inheritdoc}
   */
  public function mailStopCollecting(): void {
    $this->getCore()->mailStopCollecting();
  }

  /**
   * {@inheritdoc}
   */
  public function mailGet(): array {
    return $this->getCore()->mailGet();
  }

  /**
   * {@inheritdoc}
   */
  public function mailClear(): void {
    $this->getCore()->mailClear();
  }

  /**
   * {@inheritdoc}
   */
  public function mailSend(string $body, string $subject, string $to, string $langcode): bool {
    return $this->getCore()->mailSend($body, $subject, $to, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleInstall(string $module_name): void {
    $this->getCore()->moduleInstall($module_name);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleUninstall(string $module_name): void {
    $this->getCore()->moduleUninstall($module_name);
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions, ?string $id = NULL, ?string $label = NULL): string {
    return $this->getCore()->roleCreate($permissions, $id, $label);
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete(string $role_name): void {
    $this->getCore()->roleDelete($role_name);
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
  public function userAddRole(\stdClass $user, string $role): void {
    $this->getCore()->userAddRole($user, $role);
  }

  /**
   * {@inheritdoc}
   */
  public function watchdogFetch(int $count = 10, ?string $type = NULL, ?string $severity = NULL): string {
    return $this->getCore()->watchdogFetch($count, $type, $severity);
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
      if (!file_exists($this->drupalRoot . $path)) {
        continue;
      }

      require_once $this->drupalRoot . $path;
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
   *
   * Subclasses override this to return a synthetic version for testing the
   * non-numeric and sub-10 branches of 'detectMajorVersion()'.
   */
  protected function readVersionConstant(): string {
    return \Drupal::VERSION;
  }

  /**
   * Returns the core as an authentication-capable object, or NULL.
   *
   * @return \Drupal\Driver\Capability\AuthenticationCapabilityInterface|null
   *   The authentication-capable core, or NULL if unsupported.
   */
  protected function getAuthCore(): ?AuthenticationCapabilityInterface {
    $core = $this->getCore();

    return $core instanceof AuthenticationCapabilityInterface ? $core : NULL;
  }

}
