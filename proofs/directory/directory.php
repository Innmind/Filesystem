<?php
declare(strict_types=1);

use Innmind\Filesystem\Directory\Directory;
use Fixtures\Innmind\Filesystem\{
    Name,
    File,
};
use Fixtures\Innmind\Immutable\Sequence;
use Properties\Innmind\Filesystem\Directory as PDirectory;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Empty Directory properties',
        given(
            PDirectory::properties(),
            Name::any(),
        ),
        function($assert, $properties, $name) {
            $properties->ensureHeldBy($assert, Directory::of($name));
        },
    );
    yield proof(
        'Non EmptyDirectory properties',
        given(
            PDirectory::properties(),
            Name::any(),
            Sequence::of(
                Set\Randomize::of(File::any()),
                Set\Integers::between(1, 5), // only to speed up tests
            ),
        )->filter(
            static fn($properties, $name, $files) => $files
                ->groupBy(static fn($file) => $file->name()->toString())
                ->size() === $files->size(), // do not accept duplicated files
        ),
        function($assert, $properties, $name, $files) {
            $properties->ensureHeldBy($assert, Directory::of($name, $files));
        },
    );

    foreach (PDirectory::list() as $property) {
        yield proof(
            'Empty Directory property',
            given(
                $property,
                Name::any(),
            ),
            function($assert, $property, $name) {
                $directory = Directory::of($name);

                if ($property->applicableTo($directory)) {
                    $property->ensureHeldBy($assert, $directory);
                }
            },
        );
        yield proof(
            'Non EmptyDirectory property',
            given(
                $property,
                Name::any(),
                Sequence::of(
                    Set\Randomize::of(File::any()),
                    Set\Integers::between(1, 5), // only to speed up tests
                ),
            )->filter(
                static fn($property, $name, $files) => $files
                    ->groupBy(static fn($file) => $file->name()->toString())
                    ->size() === $files->size(), // do not accept duplicated files
            ),
            function($assert, $property, $name, $files) {
                $directory = Directory::of($name, $files);

                if ($property->applicableTo($directory)) {
                    $property->ensureHeldBy($assert, $directory);
                }
            },
        );
    }
};
