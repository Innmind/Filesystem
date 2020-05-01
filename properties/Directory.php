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
        return Set\Properties::of(
            new Directory\MediaTypeIsAlwaysTheSame,
            new Directory\ContainsMethodAlwaysReturnTrueForFilesInTheDirectory,
            new Directory\AllFilesInTheDirectoryAreAccessible,
            new Directory\AccessingUnknownFileThrowsAnException,
            new Directory\RemovingAnUnknownFileHasNoEffect,
            new Directory\RemoveFile,
            new Directory\RemoveDirectory,
            new Directory\ContentHoldsTheNamesOfTheFiles,
            new Directory\AddFile,
            new Directory\AddDirectory,
        );
    }
}
