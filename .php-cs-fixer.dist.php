<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return new Config()
    ->setRiskyAllowed(false)
    ->setCacheFile(__DIR__ . '/tmp/cs-fixer')
    ->setRules([
        '@auto' => true, // @PER-CS + PHP migration level from composer.json
    ])
    ->setFinder(
        new Finder()
            ->in(__DIR__ . '/app')
            ->in(__DIR__ . '/config')
            ->in(__DIR__ . '/database')
            ->in(__DIR__ . '/routes')
            ->in(__DIR__ . '/tests')
            ->name('*.php')
            ->append([
                __FILE__, // Include this config file itself
                __DIR__ . '/rector.php',
                __DIR__ . '/artisan',
                __DIR__ . '/bootstrap/app.php',
                __DIR__ . '/bootstrap/providers.php',
                __DIR__ . '/public/index.php',
            ]),
    );
