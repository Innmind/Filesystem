<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\{
    Directory\Directory as Model,
    File as FileInterface,
};
use Innmind\BlackBox\Set as DataSet;
use Fixtures\Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;

final class Directory
{
    /**
     * Will generate random directory tree with a maximum depth of 3 directories
     *
     * @return DataSet<Model>
     */
    public static function any(): DataSet
    {
        return self::atDepth(0, 3);
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
            $files = Set::of(
                FileInterface::class,
                new DataSet\Randomize(
                    File::any(),
                ),
                DataSet\Integers::between(0, 10),
            );
            $toAdd = DataSet\Sequence::of(
                new DataSet\Randomize(
                    File::any(),
                ),
                DataSet\Integers::between(0, 10),
            );
        } else {
            $files = Set::of(
                FileInterface::class,
                new DataSet\Either(
                    new DataSet\Randomize(
                        File::any(),
                    ),
                    self::atDepth($depth + 1, $maxDepth),
                ),
                DataSet\Integers::between(0, 10),
            )->filter(static function($files): bool {
                if ($files->empty()) {
                    return true;
                }

                // do not accept duplicated files
                return $files
                    ->groupBy(static fn($file) => $file->name()->toString())
                    ->size() === $files->size();
            });
            $toAdd = DataSet\Sequence::of(
                new DataSet\Either(
                    new DataSet\Randomize(
                        File::any(),
                    ),
                    self::atDepth($depth + 1, $maxDepth),
                ),
                DataSet\Integers::between(0, 10),
            );
        }

        return DataSet\Composite::immutable(
            static function($name, $files, $toAdd, $numberToRemove): Model {
                $directory = new Model(
                    $name,
                    $files,
                );

                foreach ($toAdd as $file) {
                    $directory = $directory->add($file);
                }

                $files = \array_merge(
                    unwrap($files),
                    $toAdd,
                );
                $toRemove = \array_slice($files, 0, $numberToRemove);

                foreach ($toRemove as $file) {
                    $directory = $directory->remove($file->name());
                }

                return $directory;
            },
            Name::any(),
            $files,
            $toAdd,
            DataSet\Integers::between(0, 20),
        );
    }
}
