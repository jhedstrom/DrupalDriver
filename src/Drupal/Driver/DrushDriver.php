<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;
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
  private bool $bootstrapped = FALSE;

  /**
   * Random generator.
   */
  private readonly Random $random;

  /**
   * Global arguments or options for drush commands.
   */
  private string $arguments = '';

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
    if (!empty($alias)) {
      // Trim off the '@' symbol if it has been added.
      $alias = ltrim($alias, '@');

      $this->alias = $alias;
    }
    elseif (!empty($root_path)) {
      $this->root = realpath($root_path);
    }
    else {
      throw new BootstrapException('A drush alias or root path is required.');
    }

    // When the default 'drush' binary is used, try to resolve the
    // project-level Drush binary first.
    if ($binary === 'drush') {
      $binary = $this->resolveProjectDrush($binary);
    }

    $this->binary = $binary;

    if (!isset($random)) {
      $random = new Random();
    }
    $this->random = $random;
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
   * {@inheritdoc}
   */
  public function getRandom(): Random {
    return $this->random;
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap(): void {
    // Check that the given alias works.
    // @todo check that this is a functioning alias.
    // See http://drupal.org/node/1615450
    if ($this->alias === NULL && $this->root === NULL) {
      throw new BootstrapException('A drush alias or root path is required.');
    }

    // Determine if drush version is legacy.
    if (!isset(self::$isLegacyDrush)) {
      self::$isLegacyDrush = $this->isLegacyDrush();
    }

    $this->bootstrapped = TRUE;
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
   * {@inheritdoc}
   */
  public function isBootstrapped(): bool {
    return $this->bootstrapped;
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user): void {
    $arguments = [
      sprintf('"%s"', $user->name),
    ];
    $options = [
      'password' => $user->pass,
      'mail' => $user->mail,
    ];
    $result = $this->drush('user-create', $arguments, $options);
    if ($uid = $this->parseUserId($result)) {
      $user->uid = $uid;
    }
    if (isset($user->roles) && is_array($user->roles)) {
      foreach ($user->roles as $role) {
        $this->userAddRole($user, $role);
      }
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
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user): void {
    $arguments = [sprintf('"%s"', $user->name)];
    $options = [
      'yes' => NULL,
      'delete-content' => NULL,
    ];
    $this->drush('user-cancel', $arguments, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, string $role): void {
    $arguments = [
      sprintf('"%s"', $role),
      sprintf('"%s"', $user->name),
    ];
    $this->drush('user-add-role', $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchWatchdog(int $count = 10, ?string $type = NULL, ?string $severity = NULL): string {
    $options = [
      'count' => $count,
      'type' => $type,
      'severity' => $severity,
    ];
    return $this->drush('watchdog-show', [], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache(?string $type = 'all'): void {
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
  public function clearStaticCaches(): void {
    // The drush driver does each operation as a separate request;
    // therefore, 'clearStaticCaches' can be a no-op.
  }

  /**
   * Decodes JSON object returned by Drush.
   *
   * It will clean up any junk that may have appeared before or after the
   * JSON object. This can happen with remote Drush aliases.
   *
   * @param string $output
   *   The output from Drush.
   *
   * @return mixed
   *   The decoded JSON value.
   */
  protected function decodeJsonObject(string $output): mixed {
    // Remove anything before the first '{'.
    $output = preg_replace('/^[^\{]*/', '', $output);
    // Remove anything after the last '}'.
    $output = preg_replace('/[^\}]*$/s', '', (string) $output);
    return json_decode((string) $output);
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreate(string $entity_type, \stdClass $entity): object {
    $payload = [
      'entity_type' => $entity_type,
      'entity' => $entity,
    ];

    return $this->callBehatCommand('create-entity', $payload);
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete(string $entity_type, object $entity): void {
    $payload = [
      'entity_type' => $entity_type,
      'entity' => $entity,
    ];
    $this->drush('behat', ['delete-entity', escapeshellarg(json_encode($payload))], []);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCreate(\stdClass $node): object {
    if (isset($node->author)) {
      $user_output = $this->drush('user-information', [sprintf('"%s"', $node->author)]);
      $uid = $this->parseUserId($user_output);

      if ($uid) {
        $node->uid = $uid;
      }
    }

    return $this->callBehatCommand('create-node', $node);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete(object $node): void {
    $this->drush('behat', ['delete-node', escapeshellarg(json_encode($node))], []);
  }

  /**
   * {@inheritdoc}
   */
  public function termCreate(\stdClass $term): object {
    return $this->callBehatCommand('create-term', $term);
  }

  /**
   * Calls a behat drush sub-command and decodes the JSON response.
   *
   * @param string $sub_command
   *   The behat sub-command (e.g., 'create-node', 'create-term').
   * @param mixed $payload
   *   The payload to JSON-encode and pass to the command.
   *
   * @return object
   *   The decoded response object.
   */
  protected function callBehatCommand(string $sub_command, mixed $payload): object {
    $result = $this->drush('behat', [$sub_command, escapeshellarg(json_encode($payload))], []);

    return $this->decodeJsonObject($result);
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(object $term): bool {
    $this->drush('behat', ['delete-term', escapeshellarg(json_encode($term))], []);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isField(string $entity_type, string $field_name): bool {
    // If the Behat Drush Endpoint is not installed on the site-under-test,
    // then the drush() method will throw an exception. In this instance, we
    // want to treat all potential fields as non-fields.  This allows the
    // Drush Driver to work with certain built-in Drush capabilities (e.g.
    // creating users) even if the Behat Drush Endpoint is not available.
    try {
      $value = [$entity_type, $field_name];
      $arguments = ['is-field', escapeshellarg(json_encode($value))];
      $result = $this->drush('behat', $arguments, []);
      return str_contains($result, "true\n");
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField(string $entity_type, string $field_name): bool {
    // Drush does not expose base-field introspection without extra modules;
    // return FALSE as a safe default so consumers treat the field as
    // non-base.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function configGet(string $name, string $key = ''): mixed {
    $arguments = $key !== '' ? [$name, $key] : [$name];
    $output = trim($this->drush('config:get', $arguments, ['format' => 'json']));

    // 'drush config:get' returns whatever JSON shape the value has (object,
    // array, scalar). 'decodeJsonObject()' would strip non-object payloads,
    // so decode the trimmed output directly.
    return json_decode($output);
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
  public function roleCreate(array $permissions): string {
    $random = $this->getRandom();
    $rid = strtolower($random->name(8, TRUE));
    $label = trim($random->name(8, TRUE));

    $this->drush('role:create', [$rid, sprintf('"%s"', $label)], []);

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
    $alias = $this->alias !== NULL ? '@' . $this->alias : '--root=' . $this->root;
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
   * {@inheritdoc}
   */
  public function processBatch(): void {
    // Do nothing. Drush should internally handle any needs for processing
    // batch ops.
  }

  /**
   * {@inheritdoc}
   */
  public function runCron(): bool {
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

}
