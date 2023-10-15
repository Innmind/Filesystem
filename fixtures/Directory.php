<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\Directory as Model;
use Properties\Innmind\Filesystem\Directory as Properties;
use Innmind\BlackBox\{
    Set as DataSet,
    Runner\Assert,
    Runner\Stats,
};
use Fixtures\Innmind\Immutable\Sequence;

final class Directory
{
    /**
     * Will generate random directory tree with a maximum depth of 3 directories
     *
     * @return DataSet<Model>
     */
    public static function any(): DataSet
    {
        return self::atDepth(0, 1);
    }

    /**
     * @return DataSet<Model>
     */
    public static function maxDepth(int $depth): DataSet
    {
        return self::atDepth(0, $depth);
    }

    private static function atDepth(int $depth, int $maxDepth): DataSet
    {
        if ($depth === $maxDepth) {
            $files = Sequence::of(
                DataSet\Randomize::of(
                    File::any(),
                ),
                DataSet\Integers::between(0, 5),
            );
        } else {
            $files = Sequence::of(
                DataSet\Either::any(
                    DataSet\Randomize::of(
                        File::any(),
                    ),
                    self::atDepth($depth + 1, $maxDepth),
                ),
                DataSet\Integers::between(0, 5),
            );
        }

        $directory = DataSet\Composite::immutable(
            static fn($name, $files): Model => Model::of(
                $name,
                $files,
            ),
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

        $modified = DataSet\Composite::immutable(
            static fn($directory, $properties): Model => $properties->ensureHeldBy(
                // not ideal but no other simple way for now
                Assert::of(Stats::new()),
                $directory,
            ),
            $directory,
            DataSet\Properties::any(
                Properties\RemoveFile::any(),
                Properties\AddFile::any(),
            )->atMost(10),
        );

        return DataSet\Either::any(
            $directory,
            $modified,
        );
    }
}
