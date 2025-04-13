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
     * @return Set\Provider<Property>|Set<Properties>
     */
    public static function properties(): Set\Provider|Set
    {
        return Set\Properties::any(...self::list())->atMost(50);
    }

    /**
     * @return non-empty-list<Set<Property>>
     */
    public static function list(): array
    {
        return \array_map(
            static fn($property) => [$property, 'any'](),
            self::all(),
        );
    }

    /**
     * @return non-empty-list<class-string<Property>>
     */
    public static function all(): array
    {
        return [
            Directory\ContainsMethodAlwaysReturnTrueForFilesInTheDirectory::class,
            Directory\AllFilesInTheDirectoryAreAccessible::class,
            Directory\AccessingUnknownFileReturnsNothing::class,
            Directory\RemovingAnUnknownFileHasNoEffect::class,
            Directory\RemoveFile::class,
            Directory\RemoveDirectory::class,
            Directory\AddFile::class,
            Directory\AddDirectory::class,
            Directory\FilteringDoesntAffectTheDirectory::class,
            Directory\FilteringRetunsTheExpectedElements::class,
            Directory\AllFilesAreAccessible::class,
            Directory\MapFiles::class,
            Directory\ThrowWhenMappingToSameFileTwice::class,
            Directory\FlatMapFiles::class,
            Directory\ThrowWhenFlatMappingToSameFileTwice::class,
            Directory\Rename::class,
        ];
    }

    /**
     * @return non-empty-list<class-string<Property>>
     */
    public static function alwaysApplicable(): array
    {
        return [
            Directory\ContainsMethodAlwaysReturnTrueForFilesInTheDirectory::class,
            Directory\AllFilesInTheDirectoryAreAccessible::class,
            Directory\AddFile::class,
            Directory\FilteringDoesntAffectTheDirectory::class,
            Directory\FilteringRetunsTheExpectedElements::class,
            Directory\AllFilesAreAccessible::class,
            Directory\Rename::class,
        ];
    }
}
