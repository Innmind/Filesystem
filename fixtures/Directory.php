<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\{
    Directory\Directory as Model,
    File as FileInterface,
};
use Properties\Innmind\Filesystem\Directory as Properties;
use Innmind\BlackBox\{
    Set as DataSet,
    Properties as Ensure,
};
use Fixtures\Innmind\Immutable\Set;

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
            $files = Set::of(
                new DataSet\Randomize(
                    File::any(),
                ),
                DataSet\Integers::between(0, 5),
            );
        } else {
            $files = Set::of(
                new DataSet\Either(
                    new DataSet\Randomize(
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
            static fn($directory, $properties): Model => $properties->ensureHeldBy($directory),
            $directory,
            DataSet\Decorate::immutable(
                static fn(array $properties): Ensure => new Ensure(...$properties),
                DataSet\Sequence::of(
                    new DataSet\Either(
                        DataSet\Property::of(
                            Properties\RemoveFile::class,
                        ),
                        DataSet\Property::of(
                            Properties\AddFile::class,
                            File::any(),
                        ),
                    ),
                    DataSet\Integers::between(1, 10),
                ),
            ),
        );

        return new DataSet\Either(
            $directory,
            $modified
        );
    }
}
