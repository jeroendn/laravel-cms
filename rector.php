<?php

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withCache(__DIR__ . '/tmp/rector', FileCacheStorage::class)
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
        __DIR__ . '/rector.php',
        __DIR__ . '/.php-cs-fixer.dist.php',
        __DIR__ . '/artisan',
        __DIR__ . '/bootstrap/app.php',
        __DIR__ . '/bootstrap/providers.php',
        __DIR__ . '/public/index.php',
    ])
    ->withPhpSets()
    ->withImportNames();
