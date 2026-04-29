<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;
use Drupal\Driver\Entity\EntityStubInterface;
use Drupal\Driver\Exception\BootstrapException;
use Symfony\Component\Process\Process;

/**
 * Drives a Drupal site via the Drush CLI.
 */
class DrushDriver implements DrushDriverInterface {
  /**
   * Store a drush alias for tests requiring shell access.
   */
  public string $alias;

  /**
   * Stores the root path to a Drupal installation.
   *
   * This is an alternative to using drush aliases.
   */
  public string $root;

  /**
   * Store the path to drush binary.
   */
  public string $binary;

  /**
   * Track bootstrapping.
   */
  protected bool $bootstrapped = FALSE;

  /**
   * Random generator.
   */
  protected readonly Random $random;

  /**
   * Global arguments or options for drush commands.
   */
  protected string $arguments = '';

  /**
   * Tracks legacy drush.
   */
  protected static bool $isLegacyDrush;

  /**
   * Set drush alias or root path.
   *
   * @param string $alias
   *   A drush alias.
   * @param string $root_path
   *   The root path of the Drupal install. This is an alternative to using
   *   aliases.
   * @param string $binary
   *   The path to the drush binary.
   * @param \Drupal\Component\Utility\Random $random
   *   Random generator.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when a required parameter is missing.
   */
  public function __construct(?string $alias = NULL, ?string $root_path = NULL, string $binary = 'drush', ?Random $random = NULL) {
    if (empty($alias) && empty($root_path)) {
      throw new BootstrapException('A drush alias or root path is required.');
    }

    if (!empty($alias)) {
      // Trim off the '@' symbol if it has been added.
      $this->alias = ltrim($alias, '@');
    }
    else {
      $this->root = realpath($root_path);
    }

    // When the default 'drush' binary is used, try to resolve the
    // project-level Drush binary first.
    if ($binary === 'drush') {
      $binary = $this->resolveProjectDrush($binary);
    }

    $this->binary = $binary;
    $this->random = $random ?? new Random();
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom(): Random {
    return $this->random;
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap(): void {
    if (!isset(self::$isLegacyDrush)) {
      self::$isLegacyDrush = $this->isLegacyDrush();
    }

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
    // Do nothing. Drush should internally handle any needs for processing
    // batch ops.
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear(?string $type = 'all'): void {
    if (self::$isLegacyDrush) {
      $this->drush('cache-clear', [$type], []);
      return;
    }

    // Drush-only cache clear does not need a full rebuild.
    if ($type === 'drush') {
      $this->drush('cache-clear', ['drush'], []);
      return;
    }

    // Both 'all' and 'drush' clear the drush cache first.
    if ($type === 'all') {
      $this->drush('cache-clear', ['drush'], []);
    }

    $this->drush('cache:rebuild');
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClearStatic(): void {
    // The drush driver does each operation as a separate request;
    // therefore, 'cacheClearStatic' can be a no-op.
  }

  /**
   * {@inheritdoc}
   */
  public function configGet(string $name, string $key = ''): mixed {
    $arguments = $key !== '' ? [$name, $key] : [$name];
    $output = trim($this->drush('config:get', $arguments, ['format' => 'json']));

    // 'drush config:get' returns whatever JSON shape the value has (object,
    // array, scalar). Decode objects to associative arrays so the return
    // shape matches 'Core::configGet()', which delegates to Drupal's config
    // API and hands back arrays.
    return json_decode($output, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function configGetOriginal(string $name, string $key = ''): mixed {
    // Drush persists every 'configSet' change to the active store; there is
    // no separate "original" layer to read, so this returns the same value
    // as 'configGet'.
    return $this->configGet($name, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet(string $name, string $key, mixed $value): void {
    $payload = json_encode($value);
    $this->drush('config:set', [$name, $key, escapeshellarg((string) $payload)], [
      'yes' => NULL,
      'input-format' => 'json',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function cronRun(): bool {
    $this->drush('cron');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleInstall(string $module_name): void {
    $this->drush('pm-enable', [$module_name], ['yes' => NULL]);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleUninstall(string $module_name): void {
    $this->drush('pm-uninstall', [$module_name], ['yes' => NULL]);
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions, ?string $id = NULL, ?string $label = NULL): string {
    $random = $this->getRandom();
    $rid = $id ?? strtolower($random->name(8, TRUE));
    $role_label = $label ?? ($id ?? trim($random->name(8, TRUE)));

    $this->drush('role:create', [$rid, sprintf('"%s"', $role_label)], []);

    foreach ($permissions as $permission) {
      $this->drush('role:perm:add', [$rid, sprintf('"%s"', $permission)], []);
    }

    return $rid;
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete(string $role_name): void {
    $this->drush('role:delete', [$role_name], []);
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(EntityStubInterface $stub): void {
    $arguments = [escapeshellarg((string) $stub->getValue('name'))];
    $options = [
      'password' => escapeshellarg((string) $stub->getValue('pass')),
      'mail' => escapeshellarg((string) $stub->getValue('mail')),
    ];

    $result = $this->drush('user-create', $arguments, $options);
    $uid = $this->parseUserId($result);

    if ($uid) {
      $stub->setValue('uid', $uid);
    }

    $roles = $stub->getValue('roles');

    if (!is_array($roles)) {
      return;
    }

    foreach ($roles as $role) {
      $this->userAddRole($stub, $role);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(EntityStubInterface $stub): void {
    $arguments = [escapeshellarg((string) $stub->getValue('name'))];
    $options = [
      'yes' => NULL,
      'delete-content' => NULL,
    ];
    $this->drush('user-cancel', $arguments, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(EntityStubInterface $stub, string $role): void {
    $arguments = [
      escapeshellarg($role),
      escapeshellarg((string) $stub->getValue('name')),
    ];
    $this->drush('user-add-role', $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function watchdogFetch(int $count = 10, ?string $type = NULL, ?string $severity = NULL): string {
    // parseArguments() maps NULL values to bare --flag, so only include
    // filters that have been explicitly set.
    $options = ['count' => $count];

    if ($type !== NULL) {
      $options['type'] = $type;
    }

    if ($severity !== NULL) {
      $options['severity'] = $severity;
    }

    return $this->drush('watchdog-show', [], $options);
  }

  /**
   * Sets common drush arguments or options.
   *
   * @param string $arguments
   *   Global arguments to add to every drush command.
   */
  public function setArguments(string $arguments): void {
    $this->arguments = $arguments;
  }

  /**
   * Get common drush arguments.
   */
  public function getArguments(): string {
    return $this->arguments;
  }

  /**
   * Execute a drush command.
   *
   * @param string $command
   *   The Drush command to execute.
   * @param array<int, string> $arguments
   *   Positional arguments to pass to Drush.
   * @param array<string, string|bool|null> $options
   *   Options to pass to Drush.
   */
  public function drush(string $command, array $arguments = [], array $options = []): string {
    $argument_string = implode(' ', $arguments);

    if (isset(static::$isLegacyDrush) && static::$isLegacyDrush) {
      $options['nocolor'] = TRUE;
    }
    else {
      $options['no-ansi'] = NULL;
    }

    $option_string = static::parseArguments($options);
    $alias = isset($this->alias) ? '@' . $this->alias : '--root=' . $this->root;
    $global = $this->getArguments();

    $cmd = sprintf('%s %s %s %s %s %s', $this->binary, $alias, $option_string, $global, $command, $argument_string);
    $process = method_exists(Process::class, 'fromShellCommandline') ? Process::fromShellCommandline($cmd) : new Process($cmd);
    $process->setTimeout(3600);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    // Some Drush commands write to stderr instead of stdout.
    if ($process->getOutput() === '' || $process->getOutput() === '0') {
      return $process->getErrorOutput();
    }

    return $process->getOutput();
  }

  /**
   * Run Drush commands dynamically from a DrupalContext.
   *
   * @param string $name
   *   The method name, used as a Drush command.
   * @param array<int, string> $arguments
   *   The method arguments, forwarded to Drush.
   *
   * @return string
   *   The Drush command output.
   */
  public function __call(string $name, array $arguments): string {
    return $this->drush($name, $arguments);
  }

  /**
   * Resolves the project-level Drush binary path.
   *
   * @param string $fallback
   *   The fallback binary path if project-level Drush is not found.
   *
   * @return string
   *   The resolved binary path.
   */
  protected function resolveProjectDrush(string $fallback): string {
    // Try Composer's runtime bin directory.
    $composer_bin = getenv('COMPOSER_BIN_DIR');
    if ($composer_bin && file_exists($composer_bin . '/drush')) {
      return $composer_bin . '/drush';
    }

    // Try common vendor/bin location relative to working directory.
    $cwd = getcwd();
    if ($cwd && file_exists($cwd . '/vendor/bin/drush')) {
      return $cwd . '/vendor/bin/drush';
    }

    return $fallback;
  }

  /**
   * Determine if drush is a legacy version.
   *
   * @return bool
   *   Returns TRUE if drush is older than drush 9.
   */
  protected function isLegacyDrush(): bool {
    try {
      // Try for a drush 9 version.
      $output = trim($this->drush('version', [], ['format' => 'string']));
      // On PHP 8.4, deprecation warnings from Drush dependencies may be
      // written to stdout before the version string. Extract the actual
      // version number from the output to avoid misdetection.
      $version = preg_match('/(\d+\.\d+\.\d+(\.\d+)?)\s*$/', $output, $matches) ? $matches[1] : $output;
      return version_compare($version, '9', '<=');
    }
    catch (\RuntimeException) {
      // The version of drush is old enough that only `--version` was available,
      // so this is a legacy version.
      return TRUE;
    }
  }

  /**
   * Parse user id from drush user-information output.
   *
   * Supports both the legacy key-value format ("User ID : 123") and the
   * Drush 12+ table format where the ID is the first numeric value in the
   * data row.
   */
  protected function parseUserId(string $info): ?int {
    // Legacy format: "User ID : 123".
    if (preg_match('/User ID\s+:\s+(\d+)/', $info, $matches)) {
      return (int) $matches[1];
    }

    // Drush 12+ table format: extract the first numeric value from the first
    // data row (the row after the header separator).
    if (preg_match('/User ID/', $info)) {
      $lines = explode("\n", trim($info));

      foreach ($lines as $line) {
        // Skip header, separator, and empty lines.
        $trimmed = trim($line, " \t\n\r\0\x0B-");

        if ($trimmed === '') {
          continue;
        }

        if (str_contains($trimmed, 'User ID')) {
          continue;
        }

        // The first column in the data row is the User ID.
        if (preg_match('/^\s*(\d+)\s/', $line, $matches)) {
          return (int) $matches[1];
        }
      }
    }

    return NULL;
  }

  /**
   * Parse arguments into a string.
   *
   * @param array<string, string|null> $arguments
   *   An array of argument/option names to values.
   *
   * @return string
   *   The parsed arguments.
   */
  protected static function parseArguments(array $arguments): string {
    $option_string = '';

    foreach ($arguments as $name => $value) {
      if ($value === NULL) {
        $option_string .= ' --' . $name;
      }
      else {
        $option_string .= ' --' . $name . '=' . $value;
      }
    }
    return $option_string;
  }

}
