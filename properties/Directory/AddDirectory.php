<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory\Directory,
    Name,
    Event\FileWasAdded,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddDirectory implements Property
{
    private Name $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return "Add directory '{$this->name->toString()}'";
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains($this->name);
    }

    public function ensureHeldBy(object $directory): object
    {
        $file = Directory::of($this->name);

        Assert::assertFalse($directory->contains($file->name()));
        $newDirectory = $directory->add($file);
        Assert::assertNotSame($directory, $newDirectory);
        Assert::assertFalse($directory->contains($file->name()));
        Assert::assertTrue($newDirectory->contains($file->name()));
        Assert::assertSame(
            $directory->removed()->size(),
            $newDirectory->removed()->size(),
        );

        return $newDirectory;
    }
}
