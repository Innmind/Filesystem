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

return static function($prove) {
    yield $prove->properties(
        'Empty Directory properties',
        PDirectory::properties(),
        Name::any()
            ->map(Directory::of(...))
            ->map(static fn($directory) => static fn() => $directory),
    );

    yield $prove->properties(
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
        )->map(static fn($directory) => static fn() => $directory),
    );

    foreach (PDirectory::alwaysApplicable() as $property) {
        yield $prove
            ->property(
                $property,
                Name::any()
                    ->map(Directory::of(...))
                    ->map(static fn($directory) => static fn() => $directory),
            )
            ->named('Empty Directory');

        yield $prove
            ->property(
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
                )->map(static fn($directory) => static fn() => $directory),
            )
            ->named('Non empty Directory');
    }
};
