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
final class AddFile implements Property
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
        return !$directory->contains($this->file->name());
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        $assert->false($directory->contains($this->file->name()));
        $newDirectory = $directory->add($this->file);
        $assert
            ->expected($directory)
            ->not()
            ->same($newDirectory);
        $assert->false($directory->contains($this->file->name()));
        $assert->true($newDirectory->contains($this->file->name()));
        $assert->same(
            $directory->removed()->size(),
            $newDirectory->removed()->size(),
        );

        return $newDirectory;
    }
}
