<?php
declare(strict_types = 1);

use Innmind\Filesystem\Directory;
use Fixtures\Innmind\Filesystem\{
    Name,
    File,
};
use Fixtures\Innmind\Immutable\Sequence;
use Properties\Innmind\Filesystem\Directory as PDirectory;
use Innmind\BlackBox\Set;

return static function() {
    yield properties(
        'Empty Directory properties',
        PDirectory::properties(),
        Name::any()->map(Directory::of(...)),
    );
    yield properties(
        'Non empty Directory properties',
        PDirectory::properties(),
        Set::compose(
            Directory::of(...),
            Name::any(),
            Sequence::of(
                File::any()->randomize(),
                Set::integers()
                    ->between(1, 5) // only to speed up tests
                    ->toSet(),
            )->filter(
                static fn($files) => $files
                    ->groupBy(static fn($file) => $file->name()->toString())
                    ->size() === $files->size(), // do not accept duplicated files
            ),
        ),
    );

    foreach (PDirectory::alwaysApplicable() as $property) {
        yield property(
            $property,
            Name::any()->map(Directory::of(...)),
        )->named('Empty Directory');
        yield property(
            $property,
            Set::compose(
                Directory::of(...),
                Name::any(),
                Sequence::of(
                    File::any()->randomize(),
                    Set::integers()
                        ->between(1, 5) // only to speed up tests
                        ->toSet(),
                )->filter(
                    static fn($files) => $files
                        ->groupBy(static fn($file) => $file->name()->toString())
                        ->size() === $files->size(), // do not accept duplicated files
                ),
            ),
        )->named('Non empty Directory');
    }
};
