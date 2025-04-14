<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\{
    Directory as Model,
    File as MFile,
};
use Innmind\BlackBox\Set;
use Fixtures\Innmind\Immutable\Sequence;

final class Directory
{
    /**
     * Will generate random directory tree with a maximum depth of 3 directories
     *
     * @return Set<Model>
     */
    public static function any(): Set
    {
        return self::atDepth(0, 1);
    }

    /**
     * @return Set<Model>
     */
    public static function maxDepth(int $depth): Set
    {
        return self::atDepth(0, $depth);
    }

    private static function atDepth(int $depth, int $maxDepth): Set
    {
        if ($depth === $maxDepth) {
            $files = Sequence::of(
                File::any()->randomize(),
                Set::integers()
                    ->between(0, 5)
                    ->toSet(),
            );
        } else {
            $files = Sequence::of(
                Set::either(
                    File::any()->randomize(),
                    self::atDepth($depth + 1, $maxDepth),
                ),
                Set::integers()
                    ->between(0, 5)
                    ->toSet(),
            );
        }

        $directory = Set::compose(
            Model::of(...),
            Name::any(),
            $files->filter(static function($files): bool {
                if ($files->empty()) {
                    return true;
                }

                // do not accept duplicated files
                return $files
                    ->groupBy(static fn($file) => $file->name()->toString())
                    ->size() === $files->size();
            }),
        );

        $modified = Sequence::of(
            Set::either(
                File::any(),
                Name::any(),
            ),
            Set::integers()
                ->between(0, 10)
                ->toSet(),
        )
            ->flatMap(
                static fn($modifications) => $directory->map(
                    static fn($directory) => $modifications->unwrap()->reduce(
                        $directory,
                        static fn($directory, $modification) => match (true) {
                            $modification instanceof MFile => $directory->add($modification),
                            default => $directory->remove($modification),
                        },
                    ),
                ),
            );

        return Set::either(
            $directory,
            $modified,
        );
    }
}
