<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem;

use Innmind\BlackBox\{
    Set,
    Property,
};
use Fixtures\Innmind\Filesystem\{
    Name,
    File,
};

final class Directory
{
    /**
     * @return Set<Property>
     */
    public static function properties(): Set
    {
        return Set\Properties::any(...self::list());
    }

    /**
     * @return list<Set<Property>>
     */
    public static function list(): array
    {
        return [
            Set\Property::of(
                Directory\MediaTypeIsAlwaysTheSame::class,
            ),
            Set\Property::of(
                Directory\ContainsMethodAlwaysReturnTrueForFilesInTheDirectory::class,
            ),
            Set\Property::of(
                Directory\AllFilesInTheDirectoryAreAccessible::class,
            ),
            Set\Property::of(
                Directory\AccessingUnknownFileReturnsNothing::class,
                Name::any(),
            ),
            Set\Property::of(
                Directory\RemovingAnUnknownFileHasNoEffect::class,
                Name::any(),
            ),
            Set\Property::of(
                Directory\RemoveFile::class,
            ),
            Set\Property::of(
                Directory\RemoveDirectory::class,
            ),
            Set\Property::of(
                Directory\ContentHoldsNothing::class,
            ),
            Set\Property::of(
                Directory\AddFile::class,
                File::any(),
            ),
            Set\Property::of(
                Directory\AddDirectory::class,
                Name::any(),
            ),
            Set\Property::of(
                Directory\FilteringDoesntAffectTheDirectory::class,
            ),
            Set\Property::of(
                Directory\FilteringRetunsTheExpectedElements::class,
                File::any(),
            ),
            Set\Property::of(
                Directory\AllFilesAreAccessible::class,
            ),
            Set\Property::of(
                Directory\MapFiles::class,
                File::any(),
            ),
            Set\Property::of(
                Directory\ThrowWhenMappingToSameFileTwice::class,
                File::any(),
            ),
            Set\Property::of(
                Directory\FlatMapFiles::class,
                new Set\Randomize(File::any()),
                new Set\Randomize(File::any()),
            ),
            Set\Property::of(
                Directory\ThrowWhenFlatMappingToSameFileTwice::class,
                new Set\Randomize(File::any()),
                new Set\Randomize(File::any()),
            ),
            Set\Property::of(
                Directory\Rename::class,
                Name::any(),
            ),
        ];
    }
}
