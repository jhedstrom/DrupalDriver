<?php

/**
 * @file
 * Rector configuration.
 *
 * Usage:
 * ./vendor/bin/rector process .
 */

declare(strict_types=1);

use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src/**',
        __DIR__ . '/tests/**',
    ])
    // PHP 7.4 only - strict types and PHP 8.0+ features deferred to v3.x.
    ->withPhpSets(php74: TRUE)
    ->withPreparedSets(
        deadCode: TRUE,
        codeQuality: TRUE,
        codingStyle: TRUE,
        instanceOf: TRUE,
        earlyReturn: TRUE,
    )
    ->withSkip([
        // Conflicts with Drupal coding style.
        NewlineAfterStatementRector::class,
        // Conflicts with PHPCS snake_case variable naming.
        CatchExceptionNameMatchingTypeRector::class,
        // Too aggressive for mixed-type codebase.
        DisallowedEmptyRuleFixerRector::class,
        // Breaks 'drupal_static()' caching in 'Drupal8::getAllPermissions()':
        // assigning into the static reference is required for subsequent
        // calls to hit the cache, so the intermediate variable is not dead
        // code even though Rector thinks it is.
        ReturnEarlyIfVariableRector::class,
        // Legacy test with PHPUnit compatibility issue.
        __DIR__ . '/tests/Drupal/Tests/Driver/Drupal7FieldHandlerTest.php',
        // Dependencies.
        '*/vendor/*',
    ]);
