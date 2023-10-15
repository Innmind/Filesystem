<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory,
    File,
    Exception\DuplicatedFile,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\File as FFile;

/**
 * @implements Property<Directory>
 */
final class ThrowWhenMappingToSameFileTwice implements Property
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public static function any(): Set
    {
        return FFile::any()->map(static fn($file) => new self($file));
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
                ->map(fn() => File::of(
                    $this->file->name(),
                    $this->file->content(),
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
