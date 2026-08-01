<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

use Innmind\BlackBox\{
    Application,
    Runner\Load,
    Runner\CodeCoverage,
};

Application::new($argv)
    ->disableMemoryLimit() // because the generated trees can be quite large
    ->map(static fn($app) => match (\getenv('BLACKBOX_ENV')) {
        'extensive' => $app->scenariiPerProof(1_000),
        'coverage' => $app
            ->codeCoverage(
                CodeCoverage::of(
                    __DIR__.'/src/',
                    __DIR__.'/proofs/',
                    __DIR__.'/fixtures/',
                )
                    ->dumpTo('coverage.clover'),
            )
            ->scenariiPerProof(1),
        default => $app->scenariiPerProof(20),
    })
    ->allowProofsToNotMakeAnyAssertions()
    ->tryToProve(Load::everythingIn(__DIR__.'/proofs/'))
    ->exit();
