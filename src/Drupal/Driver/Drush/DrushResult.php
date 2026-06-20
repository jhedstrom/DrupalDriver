<?php

declare(strict_types=1);

namespace Drupal\Driver\Drush;

/**
 * Immutable result of a Drush command execution.
 *
 * Pairs the process exit code with its captured standard output and standard
 * error, mirroring the values a finished Symfony 'Process' exposes through
 * 'getExitCode()', 'getOutput()', and 'getErrorOutput()'.
 */
final readonly class DrushResult {

  /**
   * Constructs a DrushResult.
   *
   * @param int $exitCode
   *   The command exit code. Zero indicates success; a signalled process is
   *   reported as a non-zero failure.
   * @param string $output
   *   The command's captured standard output.
   * @param string $errorOutput
   *   The command's captured standard error output.
   */
  public function __construct(
    public int $exitCode,
    public string $output,
    public string $errorOutput,
  ) {
  }

}
