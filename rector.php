<?php

/**
 * @file
 * Rector configuration.
 *
 * @see https://github.com/palantirnet/drupal-rector
 * @see https://getrector.com/documentation
 */

declare(strict_types=1);

use DrupalFinder\DrupalFinderComposerRuntime;
use DrupalRector\Set\Drupal10SetList;
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

$drupalRoot = (new DrupalFinderComposerRuntime())->getDrupalRoot();
if (!$drupalRoot || !is_dir($drupalRoot . '/core')) {
    throw new \RuntimeException('Unable to locate Drupal root via DrupalFinderComposerRuntime; ensure drupal/core is installed.');
}

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src/**',
        __DIR__ . '/tests/**',
    ])
    ->withAutoloadPaths([
        $drupalRoot . '/core',
        $drupalRoot . '/modules',
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
    ->withSets([
        Drupal10SetList::DRUPAL_10,
    ])
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
        // Breaks 'drupal_static()' caching in 'Core::getAllPermissions()':
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
