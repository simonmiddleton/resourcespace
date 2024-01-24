<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Removing\Rector\FuncCall\RemoveFuncCallArgRector;
use Rector\Removing\ValueObject\RemoveFuncCallArg;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;

return static function (RectorConfig $rectorConfig): void {
    // Note: you can override processed paths when calling Rector: vendor/bin/rector process --dry-run -- include/ pages/search.php
    $rectorConfig->paths([
        __DIR__ . '/api',
        __DIR__ . '/batch',
        __DIR__ . '/include',
        __DIR__ . '/languages', # can be useful for language changes
        __DIR__ . '/pages',
        __DIR__ . '/plugins',
        __DIR__ . '/tests',
        __DIR__ . '/upgrade',

        // __DIR__ . '/css', # shouldn't contain PHP
        // __DIR__ . '/lib', # shouldn't really contain our code!
    ]);
    $rectorConfig->skip([
        __DIR__ . '/plugins/*/css',
        __DIR__ . '/plugins/*/dbstruct',
        __DIR__ . '/plugins/*/lib',
    ]);

    // TODO: run these rules over a small set of files to avoid overwhelming PR
    // Define sets of rules - https://getrector.com/documentation/set-lists
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        LevelSetList::UP_TO_PHP_82,
    ]);

    // Individual rules - https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md
    $rectorConfig->rules([
        ReturnTypeFromStrictNativeCallRector::class,
        ReturnTypeFromStrictScalarReturnExprRector::class,
    ]);


    /*
    IMPORTANT: Rector will change tabs to spaces. Unfortunately this will mean that many times it will change
    indentation in places where you might not want to. This should be a "problem" until we update the entire code base
    to only use spaces. Until then just be aware of it :).

    Common refactoring examples:

    1. Removing a functions' argument:
    -function get_edit_access($resource,$status=-999,$metadata=false,&$resourcedata="")
    +function get_edit_access($resource,$status=-999,&$resourcedata="")

    $rectorConfig->ruleWithConfiguration(
        RemoveFuncCallArgRector::class,
        [new RemoveFuncCallArg('get_edit_access', 2)]
    );

    2. TBD
    */
};
