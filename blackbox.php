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
    ->scenariiPerProof(20)
    ->when(
        \getenv('ENABLE_COVERAGE') !== false,
        static fn(Application $app) => $app
            ->codeCoverage(
                CodeCoverage::of(
                    __DIR__.'/src/',
                    __DIR__.'/proofs/',
                    __DIR__.'/fixtures/',
                )
                    ->dumpTo('coverage.clover')
                    ->enableWhen(true),
            )
            ->scenariiPerProof(1),
    )
    ->when(
        \method_exists(Application::class, 'allowProofsToNotMakeAnyAssertions'),
        static fn($app) => $app->allowProofsToNotMakeAnyAssertions(),
    )
    ->tryToProve(Load::everythingIn(__DIR__.'/proofs/'))
    ->exit();
