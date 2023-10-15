<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Directory,
    Name,
    Exception\DuplicatedFile,
};
use Innmind\Immutable\Set;
use Innmind\BlackBox\{
    Property,
    Set as DataSet,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\File as FFile;

/**
 * @implements Property<Directory>
 */
final class ThrowWhenFlatMappingToSameFileTwice implements Property
{
    private File $file1;
    private File $file2;

    public function __construct(File $file1, File $file2)
    {
        $this->file1 = $file1;
        $this->file2 = $file2;
    }

    public static function any(): DataSet
    {
        return DataSet\Composite::immutable(
            static fn(...$args) => new self(...$args),
            DataSet\Randomize::of(FFile::any()),
            DataSet\Randomize::of(FFile::any()),
        );
    }

    public function applicableTo(object $directory): bool
    {
        return $directory->files()->size() >= 2;
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        try {
            // calling toList in case it uses a lazy Set of files, so we need to
            // unwrap the list to trigger the safeguard
            $directory
                ->flatMap(fn() => Directory::of(
                    Name::of('doesntmatter'),
                    Set::of(
                        File::of(
                            $this->file1->name(),
                            $this->file1->content(),
                        ),
                        File::of(
                            $this->file2->name(),
                            $this->file2->content(),
                        ),
                    ),
                ))
                ->files()
                ->toList();

            $assert->fail('It should throw');
        } catch (\Exception $e) {
            $assert
                ->object($e)
                ->instance(DuplicatedFile::class);
        }

        return $directory;
    }
}
