<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Exception\DuplicatedFile,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class ThrowWhenMappingToSameFileTwice implements Property
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function name(): string
    {
        return 'Throw when mapping to same file twice';
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
                ->map(fn() => File\File::of(
                    $this->file->name(),
                    $this->file->content(),
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
