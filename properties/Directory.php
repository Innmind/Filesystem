<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem;

use Innmind\BlackBox\{
    Set,
    Property,
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
     * @return non-empty-list<Set<Property>>
     */
    public static function list(): array
    {
        return [
            Directory\MediaTypeIsAlwaysTheSame::any(),
            Directory\ContainsMethodAlwaysReturnTrueForFilesInTheDirectory::any(),
            Directory\AllFilesInTheDirectoryAreAccessible::any(),
            Directory\AccessingUnknownFileReturnsNothing::any(),
            Directory\RemovingAnUnknownFileHasNoEffect::any(),
            Directory\RemoveFile::any(),
            Directory\RemoveDirectory::any(),
            Directory\ContentHoldsNothing::any(),
            Directory\AddFile::any(),
            Directory\AddDirectory::any(),
            Directory\FilteringDoesntAffectTheDirectory::any(),
            Directory\FilteringRetunsTheExpectedElements::any(),
            Directory\AllFilesAreAccessible::any(),
            Directory\MapFiles::any(),
            Directory\ThrowWhenMappingToSameFileTwice::any(),
            Directory\FlatMapFiles::any(),
            Directory\ThrowWhenFlatMappingToSameFileTwice::any(),
            Directory\Rename::any(),
        ];
    }
}
