<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem;

use Innmind\BlackBox\{
    Set,
    Property,
    PHPUnit\Seeder,
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
    public static function properties(Seeder $seed): Set
    {
        return Set\Properties::of(
            new Directory\MediaTypeIsAlwaysTheSame,
            new Directory\ContainsMethodAlwaysReturnTrueForFilesInTheDirectory,
            new Directory\AllFilesInTheDirectoryAreAccessible,
            new Directory\AccessingUnknownFileThrowsAnException(
                $seed(Name::any()),
            ),
            new Directory\RemovingAnUnknownFileHasNoEffect(
                $seed(Name::any()),
            ),
            new Directory\RemoveFile,
            new Directory\RemoveFileMustUnwrapSourceDecorator,
            new Directory\RemoveDirectory,
            new Directory\ContentHoldsTheNamesOfTheFiles,
            new Directory\AddFile(
                $seed(File::any()),
            ),
            new Directory\AddFileMustUnwrapSourceDecorator(
                $seed(File::any()),
            ),
            new Directory\AddDirectory(
                $seed(Name::any()),
            ),
        );
    }
}
