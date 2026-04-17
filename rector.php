<?php

/**
 * @file
 * Rector configuration.
 *
 * Usage:
 * ./vendor/bin/rector process .
 */

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src/**',
        __DIR__ . '/tests/**',
    ])
    ->withPhpSets(php82: TRUE)
    ->withPreparedSets(
        deadCode: TRUE,
        codeQuality: TRUE,
        codingStyle: TRUE,
        typeDeclarations: TRUE,
        naming: TRUE,
        instanceOf: TRUE,
        earlyReturn: TRUE,
    )
    ->withRules([
        DeclareStrictTypesRector::class,
        YieldDataProviderRector::class,
    ])
    ->withSkip([
        // Rules added by Rector's rule sets.
        CatchExceptionNameMatchingTypeRector::class,
        ChangeSwitchToMatchRector::class,
        // Constructor property promotion mangles existing docblocks for the
        // promoted parameter and trips PHPCS multi-line declaration sniffs.
        ClassPropertyAssignToConstructorPromotionRector::class,
        CompleteDynamicPropertiesRector::class,
        CountArrayToEmptyArrayComparisonRector::class,
        DisallowedEmptyRuleFixerRector::class,
        InlineArrayReturnAssignRector::class,
        NewlineAfterStatementRector::class,
        NewlineBeforeNewAssignSetRector::class,
        NewlineBetweenClassLikeStmtsRector::class,
        RemoveAlwaysTrueIfConditionRector::class,
        RenameForeachValueVariableToMatchExprVariableRector::class,
        RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
        RenameParamToMatchTypeRector::class,
        RenameVariableToMatchMethodCallReturnTypeRector::class,
        RenameVariableToMatchNewTypeRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        // Breaks 'drupal_static()' caching in 'Drupal8::getAllPermissions()':
        // assigning into the static reference is required for subsequent
        // calls to hit the cache, so the intermediate variable is not dead
        // code even though Rector thinks it is.
        ReturnEarlyIfVariableRector::class,
        // Dependencies.
        '*/vendor/*',
    ])
    ->withFileExtensions([
        'php',
        'inc',
    ])
    ->withImportNames(importNames: TRUE, importDocBlockNames: FALSE, importShortClasses: FALSE);
