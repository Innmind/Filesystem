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
                Directory\AccessingUnknownFileThrowsAnException::class,
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
                Directory\RemoveFileMustUnwrapSourceDecorator::class,
            ),
            Set\Property::of(
                Directory\RemoveDirectory::class,
            ),
            Set\Property::of(
                Directory\ContentHoldsTheNamesOfTheFiles::class,
            ),
            Set\Property::of(
                Directory\AddFile::class,
                File::any(),
            ),
            Set\Property::of(
                Directory\AddFileMustUnwrapSourceDecorator::class,
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
                Directory\ReplacingFileAtEmptyPathIsSameAsAddingTheFile::class,
                File::any(),
            ),
            Set\Property::of(
                Directory\ReplacingFileAtUnknownPathMustThrowAnException::class,
                Name::any(),
                File::any(),
            ),
            Set\Property::of(
                Directory\ReplacingFileAtPathTargetingAFileMustThrowAnException::class,
                File::any(),
                File::any(),
            ),
            Set\Property::of(
                Directory\ReplaceFileInSubDirectory::class,
                Name::any(),
                Name::any(),
                File::any(),
            ),
        ];
    }
}
