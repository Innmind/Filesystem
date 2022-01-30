<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Event\FileWasAdded,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddFile implements Property
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function name(): string
    {
        return "Add file '{$this->file->name()->toString()}'";
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains($this->file->name());
    }

    public function ensureHeldBy(object $directory): object
    {
        Assert::assertFalse($directory->contains($this->file->name()));
        $newDirectory = $directory->add($this->file);
        Assert::assertNotSame($directory, $newDirectory);
        Assert::assertFalse($directory->contains($this->file->name()));
        Assert::assertTrue($newDirectory->contains($this->file->name()));
        Assert::assertSame(
            $directory->removed()->size(),
            $newDirectory->removed()->size(),
        );

        return $newDirectory;
    }
}
