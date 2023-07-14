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
            Adapter\AddFile::class,
            Adapter\AddEmptyDirectory::class,
            Adapter\AddDirectoryFromAnotherAdapter::class,
            Adapter\AddDirectoryFromAnotherAdapterWithFileAdded::class,
            Adapter\AddDirectoryFromAnotherAdapterWithFileRemoved::class,
            Adapter\RemoveUnknownFile::class,
            Adapter\RemoveFile::class,
            Adapter\AllRootFilesAreAccessible::class,
            Adapter\AccessingUnknownFileReturnsNothing::class,
            Adapter\AddDirectory::class,
            Adapter\AddRemoveAddModificationsStillAddTheFile::class,
            Adapter\RemoveAddRemoveModificationsDoesntAddTheFile::class,
            Adapter\ReAddingFilesHasNoSideEffect::class,
            Adapter\AddFileWithSameNameAsDirectoryDeleteTheDirectory::class,
            Adapter\RootDirectoryIsNamedRoot::class,
        ];
    }

    /**
     * @return non-empty-list<class-string<Property>>
     */
    public static function alwaysApplicable(): array
    {
        return [
            Adapter\RemoveUnknownFile::class,
            Adapter\RemoveFile::class,
            Adapter\AllRootFilesAreAccessible::class,
            Adapter\ReAddingFilesHasNoSideEffect::class,
            Adapter\RootDirectoryIsNamedRoot::class,
        ];
    }
}
