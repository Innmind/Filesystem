<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Directory\Directory,
    Name,
    Exception\DuplicatedFile,
};
use Innmind\Immutable\Set;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class ThrowWhenFlatMappingToSameFileTwice implements Property
{
    private File $file1;
    private File $file2;

    public function __construct(File $file1, File $file2)
    {
        $this->file1 = $file1;
        $this->file2 = $file2;
    }

    public function name(): string
    {
        return 'Throw when flatMapping to same file twice';
    }

    public function applicableTo(object $directory): bool
    {
        return $directory->files()->size() >= 2;
    }

    public function ensureHeldBy(object $directory): object
    {
        try {
            // calling toList in case it uses a lazy Set of files, so we need to
            // unwrap the list to trigger the safeguard
            $directory
                ->flatMap(fn() => Directory::of(
                    Name::of('doesntmatter'),
                    Set::of(
                        File\File::of(
                            $this->file1->name(),
                            $this->file1->content(),
                        ),
                        File\File::of(
                            $this->file2->name(),
                            $this->file2->content(),
                        ),
                    ),
                ))
                ->files()
                ->toList();

            Assert::fail('It should throw');
        } catch (\Exception $e) {
            Assert::assertInstanceOf(DuplicatedFile::class, $e);
        }

        return $directory;
    }
}
