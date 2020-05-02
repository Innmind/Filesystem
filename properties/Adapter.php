<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem;

use Innmind\BlackBox\{
    Set,
    Property,
    PHPUnit\Seeder,
};
use Fixtures\Innmind\Filesystem\{
    File,
    Name,
};

final class Adapter
{
    /**
     * @return Set<Property>
     */
    public static function properties(Seeder $seed): Set
    {
        return Set\Properties::of(
            new Adapter\AddFile(
                $seed(File::any()),
            ),
            new Adapter\AddEmptyDirectory(
                $seed(Name::any()),
            ),
            new Adapter\AddDirectoryFromAnotherAdapter(
                $seed(Name::any()),
                $seed(File::any()),
            ),
            new Adapter\AddDirectoryFromAnotherAdapterWithFileAdded(
                $seed(Name::any()),
                $seed(File::any()),
                $seed(File::any()),
            ),
            new Adapter\AddDirectoryFromAnotherAdapterWithFileRemoved(
                $seed(Name::any()),
                $seed(File::any()),
                $seed(File::any()),
            ),
            new Adapter\RemoveUnknownFile(
                $seed(Name::any()),
            ),
            new Adapter\RemoveFile(
                $seed(File::any()),
            ),
            new Adapter\AllRootFilesAreAccessible,
            new Adapter\AccessingUnknownFileThrowsAnException(
                $seed(Name::any()),
            ),
        );
    }
}
