<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem;

use Innmind\BlackBox\{
    Set,
    Property,
};
use Fixtures\Innmind\Filesystem\{
    File,
    Name,
    Directory,
};
use Fixtures\Innmind\Stream\Readable;

final class Adapter
{
    /**
     * @return Set<Property>
     */
    public static function properties(): Set
    {
        return Set\Properties::any(...self::list());
    }

    /**
     * @return list<Property>
     */
    public static function list(): array
    {
        return [
            Set\Property::of(
                Adapter\AddFile::class,
                File::any(),
            ),
            Set\Property::of(
                Adapter\AddEmptyDirectory::class,
                Name::any(),
            ),
            Set\Property::of(
                Adapter\AddDirectoryFromAnotherAdapter::class,
                Name::any(),
                File::any(),
            ),
            Set\Property::of(
                Adapter\AddDirectoryFromAnotherAdapterWithFileAdded::class,
                Name::any(),
                File::any(),
                File::any(),
            ),
            Set\Property::of(
                Adapter\AddDirectoryFromAnotherAdapterWithFileRemoved::class,
                Name::any(),
                File::any(),
                File::any(),
            ),
            Set\Property::of(
                Adapter\RemoveUnknownFile::class,
                Name::any(),
            ),
            Set\Property::of(
                Adapter\RemoveFile::class,
                File::any(),
            ),
            Set\Property::of(
                Adapter\AllRootFilesAreAccessible::class,
            ),
            Set\Property::of(
                Adapter\AccessingUnknownFileThrowsAnException::class,
                Name::any(),
            ),
            Set\Property::of(
                Adapter\AddDirectory::class,
                Directory::any(),
            ),
            Set\Property::of(
                Adapter\AddRemoveAddModificationsStillAddTheFile::class,
                Directory::any(),
                File::any(),
            ),
            Set\Property::of(
                Adapter\RemoveAddRemoveModificationsDoesntAddTheFile::class,
                Directory::any(),
                File::any(),
            ),
            Set\Property::of(
                Adapter\ReAddingFilesHasNoSideEffect::class,
            ),
            Set\Property::of(
                Adapter\AddFileWithSameNameAsDirectoryDeleteTheDirectory::class,
                Name::any(),
                Readable::any(),
                File::any(),
            )
        ];
    }
}
