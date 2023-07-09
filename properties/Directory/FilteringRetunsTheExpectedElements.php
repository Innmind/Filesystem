<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory,
    File,
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
final class FilteringRetunsTheExpectedElements implements Property
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
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        $shouldBeEmpty = $directory->filter(fn($file): bool => $file === $this->file);
        $shouldContainsOurFile = $directory
            ->add($this->file)
            ->filter(fn($file): bool => $file->name() === $this->file->name());

        $assert->false($shouldBeEmpty->contains($this->file->name()));
        $assert->true($shouldContainsOurFile->contains($this->file->name()));

        return $directory;
    }
}
