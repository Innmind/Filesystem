<?php
declare(strict_types=1);

use Innmind\Filesystem\Adapter\InMemory;
use Innmind\Url\Path;
use Properties\Innmind\Filesystem\Adapter;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'InMemory properties',
        given(Adapter::properties()),
        function($assert, $properties) {
            $properties->ensureHeldBy($assert, InMemory::new());
        },
    );
    yield proof(
        'InMemory properties emulating filesystem',
        given(Adapter::properties()),
        function($assert, $properties) {
            $properties->ensureHeldBy($assert, InMemory::emulateFilesystem());
        },
    );

    foreach (Adapter::list() as $property) {
        yield proof(
            'InMemory property',
            given($property),
            function($assert, $property) {
                $filesystem = InMemory::new();

                if ($property->applicableTo($filesystem)) {
                    $property->ensureHeldBy($assert, $filesystem);
                }
            },
        );
        yield proof(
            'InMemory property emulating filesystem',
            given($property),
            function($assert, $property) {
                $filesystem = InMemory::emulateFilesystem();

                if ($property->applicableTo($filesystem)) {
                    $property->ensureHeldBy($assert, $filesystem);
                }
            },
        );
    }
};
