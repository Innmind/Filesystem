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
        return Set\Properties::of(
            new Adapter\AddFile,
            new Adapter\AddEmptyDirectory,
            new Adapter\AddDirectoryFromAnotherAdapter,
            new Adapter\AddDirectoryFromAnotherAdapterWithFileAdded,
            new Adapter\AddDirectoryFromAnotherAdapterWithFileRemoved,
            new Adapter\RemoveUnknownFile,
            new Adapter\RemoveFile,
            new Adapter\AllRootFilesAreAccessible,
            new Adapter\AccessingUnknownFileThrowsAnException,
        );
    }
}
