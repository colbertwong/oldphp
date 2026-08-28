<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Utils\Rector\Rector\FirstRuleRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__,
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withSets([SetList::PHP_84])
    ->withSkipPath(__DIR__ . '/.claude')
    ->withSkipPath(__DIR__ . '/vendor')
    ->withSkipPath(__DIR__ . '/utils/rector')
    ->withFileExtensions(['php'])
    ->withRules([FirstRuleRector::class])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
