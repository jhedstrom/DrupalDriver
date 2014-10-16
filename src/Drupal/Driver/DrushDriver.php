<?php

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;
use Drupal\Driver\Exception\BootstrapException;

use Symfony\Component\Process\Process;

/**
 * Implements DriverInterface.
 */
class DrushDriver extends BaseDriver {
  /**
   * Store a drush alias for tests requiring shell access.
   *
   * @var string
   */
  public $alias;

  /**
   * Store the root path to a Drupal installation. This is an alternative to
   * using drush aliases.
   *
   * @var string
   */
  public $root;

  /**
   * Store the path to drush binary.
   *
   * @var string
   */
  public $binary;

  /**
   * Track bootstrapping.
   */
  private $bootstrapped = FALSE;

  /**
   * Random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  private $random;

  /**
   * Set drush alias or root path.
   *
   * @param string $alias
   *   A drush alias
   * @param string $root_path
   *   The root path of the Drupal install. This is an alternative to using aliases.
   * @param string $binary
   *   The path to the drush binary.
   * @param \Drupal\Component\Utility\Random $random
   *   Random generator.
   *
   * @throws \BootstrapException
   */
  public function __construct($alias = NULL, $root_path = NULL, $binary = 'drush', Random $random) {
    if (isset($alias)) {
      // Trim off the '@' symbol if it has been added.
      $alias = ltrim($alias, '@');

      $this->alias = $alias;
    }
    elseif (isset($root_path)) {
      $this->root = realpath($root_path);
    }
    else {
      throw new \BootstrapException('A drush alias or root path is required.');
    }

    $this->binary = $binary;
    $this->random = $random;
  }

  /**
   * {@inheritDoc}
   */
  public function getRandom() {
    return $this->random;
  }

  /**
   * {@inheritDoc}
   */
  public function bootstrap() {
    // Check that the given alias works.
    // @todo check that this is a functioning alias.
    // See http://drupal.org/node/1615450
    if (!isset($this->alias) && !isset($this->root)) {
      throw new \BootstrapException('A drush alias or root path is required.');
    }
    $this->bootstrapped = TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function isBootstrapped() {
    return $this->bootstrapped;
  }

  /**
   * {@inheritDoc}
   */
  public function userCreate(\stdClass $user) {
    $arguments = array(
      $user->name,
    );
    $options = array(
      'password' => $user->pass,
      'mail' => $user->mail,
    );
    $this->drush('user-create', $arguments, $options);
    if (isset($user->roles) && is_array($user->roles)) {
      foreach ($user->roles as $role) {
        $this->userAddRole($user, $role);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function userDelete(\stdClass $user) {
    $arguments = array($user->name);
    $options = array(
      'yes' => NULL,
      'delete-content' => NULL,
    );
    $this->drush('user-cancel', $arguments, $options);
  }

  /**
   * {@inheritDoc}
   */
  public function userAddRole(\stdClass $user, $role) {
    $arguments = array(
      sprintf('"%s"', $role),
      $user->name
    );
    $this->drush('user-add-role', $arguments);
  }

  /**
   * {@inheritDoc}
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    $options = array(
      'count' => $count,
      'type' => $type,
      'severity' => $severity,
    );
    return $this->drush('watchdog-show', array(), $options);
  }

  /**
   * {@inheritDoc}
   */
  public function clearCache($type = 'all') {
    $type = array($type);
    return $this->drush('cache-clear', $type, array());
  }

  /**
   * Execute a drush command.
   */
  public function drush($command, array $arguments = array(), array $options = array()) {
    $arguments = implode(' ', $arguments);
    $string_options = '';
    $options['nocolor'] = '';
    foreach ($options as $name => $value) {
      if (is_null($value)) {
        $string_options .= ' --' . $name;
      }
      else {
        $string_options .= ' --' . $name . '=' . $value;
      }
    }

    $alias = isset($this->alias) ? "@{$this->alias}" : '--root=' . $this->root;

    $process = new Process("{$this->binary} {$alias} {$command} {$string_options} {$arguments}");
    $process->setTimeout(3600);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    // Some drush commands write to standard error output (for example enable
    // use drush_log which default to _drush_print_log) instead of returning a string
    // (drush status use drush_print_pipe).
    if (!$process->getOutput()) {
      return $process->getErrorOutput();
    }
    else {
      return $process->getOutput();
    }

  }

  /**
   * {@inheritDoc}
   */
  public function processBatch() {
    // Do nothing. Drush should internally handle any needs for processing batch ops.
  }

  /**
   * {@inheritDoc}
   */
  public function runCron() {
    $this->drush('cron');
  }

  /**
   * Helper function to derive the Drupal root directory from given alias.
   */
  public function getDrupalRoot($alias = NULL) {
    if (!isset($alias)) {
      $alias = $this->alias;
    }

    // Use drush site-alias to find path.
    $path = $this->drush('site-alias', array('@' . $alias), array('pipe' => NULL));

    // Remove anything past the # that occasionally returns with site-alias.
    $path = reset(explode('#', $path));

    return $path;
  }

  /**
   * Run Drush commands dynamically from a DrupalContext.
   */
  public function __call($name, $arguments) {
    return $this->drush($name, $arguments);
  }

}
