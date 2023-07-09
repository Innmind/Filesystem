<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem;

use Innmind\BlackBox\{
    Set,
    Property,
};

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
     * @return non-empty-list<Set<Property>>
     */
    public static function list(): array
    {
        return [
            Adapter\AddFile::any(),
            Adapter\AddEmptyDirectory::any(),
            Adapter\AddDirectoryFromAnotherAdapter::any(),
            Adapter\AddDirectoryFromAnotherAdapterWithFileAdded::any(),
            Adapter\AddDirectoryFromAnotherAdapterWithFileRemoved::any(),
            Adapter\RemoveUnknownFile::any(),
            Adapter\RemoveFile::any(),
            Adapter\AllRootFilesAreAccessible::any(),
            Adapter\AccessingUnknownFileReturnsNothing::any(),
            Adapter\AddDirectory::any(),
            Adapter\AddRemoveAddModificationsStillAddTheFile::any(),
            Adapter\RemoveAddRemoveModificationsDoesntAddTheFile::any(),
            Adapter\ReAddingFilesHasNoSideEffect::any(),
            Adapter\AddFileWithSameNameAsDirectoryDeleteTheDirectory::any(),
            Adapter\RootDirectoryIsNamedRoot::any(),
        ];
    }
}
