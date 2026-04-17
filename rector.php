<?php

/**
 * @file
 * Rector configuration.
 *
 * Usage:
 * ./vendor/bin/rector process .
 */

declare(strict_types=1);

use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
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
        // Too aggressive for mixed-type codebase.
        DisallowedEmptyRuleFixerRector::class,
        // Dependencies.
        '*/vendor/*',
    ]);
